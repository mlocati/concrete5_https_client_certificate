<?php
namespace HttpsClientCertificate;

use Concrete\Core\Application\Application;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Package\PackageService;

class Configuration
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
     * The package configuration.
     *
     * @var \Concrete\Core\Config\Repository\Liaison|null
     */
    protected $packageConfig = null;

    /**
     * Initializes the instance.
     *
     * @param Application $app the Application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

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
     * Resolve the certificate file name by replacing placeholders.
     *
     * @param string $path
     *
     * @throws UserMessageException throw an exception if $path is not valid
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
     * Get the certificate path (may include placeholders).
     *
     * @return string
     */
    public function getCertificatePath()
    {
        return $this->getPackageConfig()->get('options.path');
    }

    /**
     * Set the certificate path (may include placeholders).
     *
     * @param string $value
     */
    public function setCertificatePath($value)
    {
        if ($this->getCertificatePath() !== $value) {
            $config = $this->getPackageConfig();
            $config->set('options.path', $value);
            $config->save('options.path', $value);
        }
    }

    /**
     * Get the certificate maximum age (in seconds).
     *
     * @return int
     */
    public function getCertificateMaxAge()
    {
        return $this->getPackageConfig()->get('options.maxAge');
    }

    /**
     * Set the certificate maximum age (in seconds).
     *
     * @param int $value
     */
    public function setCertificateMaxAge($value)
    {
        if ($this->getCertificateMaxAge() !== $value) {
            $config = $this->getPackageConfig();
            $config->set('options.maxAge', $value);
            $config->save('options.maxAge', $value);
        }
    }

    /**
     * Get the allowed protocol to be used to download the certificate file.
     *
     * @return string 'https', 'http' or '*' for any
     */
    public function getAllowedProtocol()
    {
        $result = $this->getPackageConfig()->get('options.remoteProtocol');
        switch ($result) {
            case 'https':
            case 'http':
                break;
            default:
                $result = '*';
                break;
        }

        return $result;
    }

    /**
     * Set the allowed protocol to be used to download the certificate file.
     *
     * @param string $value 'https', 'http' or '*' for any
     */
    public function setAllowedProtocol($value)
    {
        if ($this->getAllowedProtocol() !== $value) {
            $config = $this->getPackageConfig();
            $config->set('options.remoteProtocol', $value);
            $config->save('options.remoteProtocol', $value);
        }
    }

    /**
     * Get the remote file URIs.
     *
     * @param string|null $allowedProtocol the protocol to be used (empty for the default ones, '*' for all the available protocols, 'http' or 'https' for a specific protocol)
     *
     * @throws UserMessageException throw an exception if $allowedProtocol is not valid
     *
     * @return string[]
     */
    public function getRemoteFileURIs($allowedProtocol = null)
    {
        $config = $this->getPackageConfig();
        if ($allowedProtocol) {
            switch ($allowedProtocol) {
                case '*':
                case 'https':
                case 'http':
                    break;
                default:
                    throw new UserMessageException(t(/*i18n: %s is a parameter name*/'Invalid %s parameter received', 'remoteProtocol'));
            }
        } else {
            $allowedProtocol = $this->getAllowedProtocol();
        }
        $result = [];
        foreach (['https', 'http'] as $protocol) {
            if ($allowedProtocol === '*' || $allowedProtocol === $protocol) {
                $s = $config->get('options.remoteFileUri.' . $protocol);
                if ($s) {
                    $result[] = $s;
                }
            }
        }
        if (empty($result)) {
            throw new UserMessageException(t('No remote file URI found.'));
        }

        return $result;
    }
}
