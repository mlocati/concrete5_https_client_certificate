<?php
namespace Concrete\Package\HttpsClientCertificate\Job;

use Concrete\Core\Job\Job;
use Concrete\Core\Support\Facade\Application;
use HttpsClientCertificate\Updater;

class UpdateHttpsClientCertificate extends Job
{
    /**
     * {@inheritdoc}
     *
     * @see Job::getJobName()
     */
    public function getJobName()
    {
        return t('Update HTTPS Client Certificate');
    }

    /**
     * {@inheritdoc}
     *
     * @see Job::getJobDescription()
     */
    public function getJobDescription()
    {
        return t('Update (if necessary) the certificate used by the HTTP Client to certify HTTPS connections.');
    }

    /**
     * {@inheritdoc}
     *
     * @see Job::run()
     */
    public function run()
    {
        $app = Application::getFacadeApplication();
        $updater = $app->make(Updater::class);
        $updateResult = $updater->update();

        return (string) $updateResult;
    }
}
