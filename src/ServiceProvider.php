<?php
namespace HttpsClientCertificate;

use Concrete\Core\Foundation\Service\Provider;

/**
 * Class that register the services.
 */
class ServiceProvider extends Provider
{
    /**
     * {@inheritdoc}
     *
     * @see Provider::register()
     */
    public function register()
    {
        $this->registerSingletons();
    }

    /**
     * Register the singletons.
     */
    private function registerSingletons()
    {
        $this->app->singleton(Configuration::class);
    }
}
