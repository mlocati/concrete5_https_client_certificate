<?php
namespace HttpsClientCertificate;

use Concrete\Core\Application\Application;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\Client\Client;
use DateTime;
use Exception;
use Illuminate\Filesystem\Filesystem;

class Updater
{
    /**
     * The Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The configuration manager.
     *
     * @var Configuration
     */
    protected $configuration;

    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Initializes the instance.
     *
     * @param Application $app the Application instance
     */
    public function __construct(Application $app, Configuration $configuration, Filesystem $filesystem)
    {
        $this->app = $app;
        $this->configuration = $configuration;
        $this->filesystem = $filesystem;
    }

    /**
     * Update (if necessary) the local file used to certificate the HTTP connections.
     *
     * @param array $options {
     *
     *     @var string $path the certificate path (may include placeholders)
     *     @var int $maxAge the certificate maximum age (in seconds)
     *     @var bool $allowedProtocol the allowed protocol ('https', 'http' or '*') to be used to download the certificate file
     *     @var bool $persistOptions set to true to make the options the default ones.
     * }
     *
     * @param bool $forceUpdate Set to true to force the update of the local certificate file, even if it's already up-to-date
     *
     * @throws Exception
     *
     * @return UpdateResult
     */
    public function update(array $options = [], $forceUpdate = false)
    {
        $path = array_key_exists('path', $options) ? $options['path'] : $this->configuration->getCertificatePath();
        $file = $this->configuration->resolveCertificateFilename($path);
        $maxAge = array_key_exists('maxAge', $options) ? $options['maxAge'] : $this->configuration->getCertificateMaxAge();
        if (is_string($maxAge)) {
            $maxAge = is_numeric($maxAge) ? (int) $maxAge : 0;
        } elseif (!is_int($maxAge) && !is_float($maxAge)) {
            $maxAge = 0;
        }
        if ($maxAge <= 0) {
            throw new UserMessageException(t(/*i18n: %s is a parameter name*/'Invalid %s parameter', 'maxAge'));
        }
        $currentDateTime = null;
        $currentAge = null;
        if ($this->filesystem->isFile($file)) {
            $mtime = @$this->filesystem->lastModified($file);
            if ($mtime) {
                $currentDateTime = new DateTime('@' . $mtime);
                $currentAge = time() - $mtime;
            }
        }
        $allowedProtocol = empty($options['allowedProtocol']) ? $this->configuration->getAllowedProtocol() : $options['allowedProtocol'];
        $sourceUrls = $this->configuration->getRemoteFileURIs($allowedProtocol);
        if ($forceUpdate || $currentAge === null || $currentAge > $maxAge) {
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
            $result = UpdateResult::updated($file, new DateTime("+{$maxAge} seconds"), $currentDateTime);
        } else {
            $updateInSeconds = $maxAge - $age;
            $result = UpdateResult::alreadyUpToDate($file, $currentDateTime, new DateTime("+{$updateInSeconds} seconds"));
        }
        if (!empty($options['persistOptions'])) {
            $this->configuration->setCertificatePath($path);
            $this->configuration->setCertificateMaxAge($maxAge);
            $this->configuration->setAllowedProtocol($allowedProtocol);
        }
        $config = $this->app->make('config');
        if ($config->get('app.http_client.sslcafile') !== $file) {
            $config->set('app.http_client.sslcafile', $file);
            $config->save('app.http_client.sslcafile', $file);
        }

        return $result;
    }
}
