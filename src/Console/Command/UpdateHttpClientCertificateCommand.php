<?php

namespace HttpsClientCertificate\Console\Command;

use Concrete\Core\Console\Command;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Support\Facade\Application;
use HttpsClientCertificate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateHttpClientCertificateCommand extends Command
{
    /**
     * @var \Concrete\Core\Config\Repository\Liaison|null
     */
    protected $packageConfig = null;

    /**
     * @return \Concrete\Core\Config\Repository\Liaison
     */
    protected function getPackageConfig()
    {
        if ($this->packageConfig === null) {
            $app = Application::getFacadeApplication();
            $package = $app->make(PackageService::class)->getClass('https_client_certificate');
            $this->packageConfig = $package->getFileConfig();
        }

        return $this->packageConfig;
    }

    protected function configure()
    {
        $config = $this->getPackageConfig();
        $this
            ->setName('hcc:update-https-client-certificate')
            ->setDescription('Update (if necessari) the HTTPS client certificate')
            ->addEnvOption()
            ->addOption('max-age', 'a', InputOption::VALUE_REQUIRED, 'The maximum age (in seconds) of the certificate chain before it gets downloaded again', $config->get('options.maxAge'))
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Where to store the client certiticate file', $config->get('options.path'))
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the update of the certificate file')
            ->setHelp(<<<EOT
Returns codes:
  0 operation completed successfully
  1 errors occurred
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = Application::getFacadeApplication();
        $options = [];
        $o = $input->getOption('max-age');
        if ($o !== null) {
            $options['maxAge'] = $o;
        }
        $o = $input->getOption('path');
        if ($o !== null) {
            $options['path'] = $o;
        }
        $force = $input->getOption('force');
        $updater = $app->make(Updater::class);
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $output->write('Updating the HTTPS client certificate file... ');
        }
        $updateResult = $updater->update($options, $force);
        /* @var \HttpsClientCertificate\UpdateResult $updateResult */
        $text = $updateResult->isUpdated() ?
            t('The HTTPS Client certificate has been updated.')
            :
            t('The HTTPS Client certificate is already up-to-date.')
        ;
        $dt = $updateResult->getNextUpdate();
        if ($dt !== null) {
            $text .= "\n" . t(/*i18n: %s is a date/time*/'The file will be updated on %s', $app->make('date')->formatDateTime($dt, true, true));
        }
        $output->writeln($text);

        return 0;
    }
}
