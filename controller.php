<?php
namespace Concrete\Package\HttpsClientCertificate;

use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Package\Package;
use HttpsClientCertificate\Console\Command\UpdateHttpClientCertificateCommand;
use HttpsClientCertificate\ServiceProvider;
use HttpsClientCertificate\Updater;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{
    protected $pkgHandle = 'https_client_certificate';

    protected $appVersionRequired = '8.2.0';

    protected $pkgVersion = '0.9.0';

    protected $pkgAutoloaderRegistries = [
        'src' => 'HttpsClientCertificate',
    ];

    public function getPackageName()
    {
        return t('HTTPS Client Certificate validation');
    }

    public function getPackageDescription()
    {
        return t('Configure the concrete5 HTTP client certificates.');
    }

    public function install()
    {
        parent::install();
        $this->installXml();
        $this->registerServiceProvider();
        try {
            $this->app->make(Updater::class)->update();
        } catch (\Exception $x) {
        } catch (\Throwable $x) {
        }
    }

    public function upgrade()
    {
        $this->installXml();
        parent::upgrade();
    }

    public function uninstall()
    {
        parent::uninstall();
        $config = $this->app->make('config');
        $config->save('app.http_client.sslcafile', null);
    }

    private function installXml()
    {
        $contentImporter = $this->app->make(ContentImporter::class);
        echo $this->getPackagePath() . '/install.xml';
        $contentImporter->importContentFile($this->getPackagePath() . '/install.xml');
    }

    public function on_start()
    {
        $this->registerServiceProvider();
        if ($this->app->isRunThroughCommandLineInterface()) {
            $this->registerConsoleCommands();
        }
    }

    /**
     * Register the service classes.
     */
    private function registerServiceProvider()
    {
        $provider = $this->app->make(ServiceProvider::class);
        $provider->register();
    }

    private function registerConsoleCommands()
    {
        $console = $this->app->make('console');
        $console->add(new UpdateHttpClientCertificateCommand());
    }
}
