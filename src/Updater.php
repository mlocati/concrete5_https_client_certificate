<?php

namespace HttpsClientCertificate;

use Concrete\Core\Application\Application;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\Client\Client;
use Concrete\Core\Package\PackageService;
use DateTime;
use Exception;
use Illuminate\Filesystem\Filesystem;

class Updater
{
    /**
     * The placeholder that can be used in the path to represent the application path.
     *
     * @var string
     */
    const PLACEHOLDER_APPLICATION = '<APPLICATION>';

    /**
     * The Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The package configuration.
     *
     * @var \Concrete\Core\Config\Repository\Liaison|null
     */
    protected $packageConfig = null;

    /**
     * Get the package configuration.
     *
     * @return \Concrete\Core\Config\Repository\Liaison
     */
    protected function getPackageConfig()
    {
        if ($this->packageConfig === null) {
            $package = $this->app->make(PackageService::class)->getClass('https_client_certificate');
            $this->packageConfig = $package->getFileConfig();
        }

        return $this->packageConfig;
    }

    /**
     * Initializes the instance.
     *
     * @param Application $app the Application instance
     */
    public function __construct(Application $app, Filesystem $filesystem)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;
    }

    /**
     * Resolve the certificate file name by replacing placeholders.
     *
     * @param string $path
     *
     * @throws Exception throw an exception if $path is not valid
     *
     * @return string
     */
    public function resolveCertificateFilename($path)
    {
        $path = is_string($path) ? trim($path) : '';
        if ($path === '') {
            throw new UserMessageException(t(/*i18n: %s is a parameter name*/'Invalid %s parameter received', 'path'));
        }
        if (strpos($path, static::PLACEHOLDER_APPLICATION) === 0) {
            $path = str_replace(static::PLACEHOLDER_APPLICATION, DIR_APPLICATION, $path);
        }
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        return $path;
    }

    /**
     * Update (if necessary) the local file used to certificate the HTTP connections.
     *
     * @param array $options {
     *
     *     @var string $path
     *     @var int $maxAge
     *     @var string $force
     * }
     *
     * @param bool $forceUpdate
     *
     * @throws Exception
     *
     * @return UpdateResult
     */
    public function update(array $options = [], $forceUpdate = false)
    {
        $packageConfig = $this->getPackageConfig();
        $path = array_key_exists('path', $options) ? $options['path'] : $packageConfig->get('options.path');
        $file = $this->resolveCertificateFilename($path);
        $maxAge = array_key_exists('maxAge', $options) ? $options['maxAge'] : $packageConfig->get('options.maxAge');
        if (is_string($maxAge)) {
            $maxAge = is_numeric($maxAge) ? (int) $maxAge : 0;
        } elseif (!is_int($maxAge)) {
            $maxAge = 0;
        }
        if ($maxAge <= 0) {
            throw new UserMessageException(t(/*i18n: %s is a parameter name*/'Invalid %s parameter', 'maxAge'));
        }
        if ($forceUpdate || !$this->filesystem->isFile($file)) {
            $updateInSeconds = 0;
        } else {
            $mtime = @$this->filesystem->lastModified($file);
            if (!$mtime) {
                $updateInSeconds = 0;
            } else {
                $age = time() - $mtime;
                $updateInSeconds = $maxAge - $age;
            }
        }
        if ($updateInSeconds > 0) {
            $result = UpdateResult::alreadyUpToDate($file, new DateTime("+{$updateInSeconds} seconds"));
        } else {
            $sourceUrls = [];
            if (empty($options['force']) || $options['force'] !== 'http') {
                $sourceUrls[] = $packageConfig->get('options.remoteFileSecure');
            }
            if (empty($options['force']) || $options['force'] !== 'https') {
                $sourceUrls[] = $packageConfig->get('options.remoteFile');
            }
            $data = null;
            while (!empty($sourceUrls)) {
                try {
                    $sourceUrl = array_shift($sourceUrls);
                    $client = $this->app->make('http/client');
                    /* @var \Concrete\Core\Http\Client\Client $client */
                    $client->setUri($sourceUrl);
                    $response = $client->send();
                    if (!$response->isSuccess()) {
                        throw new Exception(t('Failed to download the HTTPS client certificates (%s)', $response->getReasonPhrase()));
                    }
                    $data = $response->getBody();
                } catch (Exception $x) {
                    if (empty($sourceUrls)) {
                        throw $x;
                    }
                }
                break;
            }
            if (!$data) {
                throw new Exception(t('No data downloaded'));
            }
            if (!$this->filesystem->put($file, $data)) {
                throw new Exception(t('Failed to save the HTTPS client certificates'));
            }
            $result = UpdateResult::updated($file, new DateTime("+{$maxAge} seconds"));
        }
        if ($packageConfig->get('options.path') !== $path) {
            $packageConfig->save('options.path', $path);
        }
        if ($packageConfig->get('options.maxAge') !== $maxAge) {
            $packageConfig->save('options.maxAge', $maxAge);
        }
        $config = $this->app->make('config');
        if ($config->get('app.http_client.sslcafile') !== $file) {
            $config->save('app.http_client.sslcafile', $file);
        }

        return $result;
    }
}
