<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Model;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Zero Composer
 */
class Composer extends Deploy
{

    /**
     * @var string[]
     */
    private $allowedOptionsForComposerInstall = [
        '--prefer-install',
        '--dry-run',
        '--dev',
        '--no-dev',
        '--no-autoloader',
        '--no-progress',
        '--audit',
        '--audit-format',
        '--optimize-autoloader',
        '-o',
        '--classmap-authoritative',
        '-a',
        '--apcu-autoloader',
        '--apcu-autoloader-prefix',
        '--ignore-platform-reqs',
        '--ignore-platform-req'
    ];

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit", '-1');

        $commandType = $input->getArgument('commandType') ?: '';
        $packageName = $input->getArgument('value') ?: '';
        $composerOptions = $input->getOption(\Magefan\ZeroDowntimeDeploy\Console\Command\Composer::COMPOSER_OPTIONS);

        $composerOptions = ($composerOptions) ? ' ' . $composerOptions . ' ' : '';

        $packageName .= $composerOptions;

        if (!in_array($commandType, ['require', 'update', 'remove'])) {
            $output->writeln('<error>'. $commandType .' - unknown command. Allowed one of: require, update, remove.</error>');
            return ;
        }

        if (!$packageName && $commandType != 'update') {
            $output->writeln('<error>Package name is not specified.</error>');
            return ;
        }

        if ($this->config->isEnabled()) {
            if ($this->fileIo->fileExists(Config::RUNNING_FLAG_FILE)) {
                $output->writeln('<comment>'
                    . __('Deploy is executed by another process. Please wait. To unlock deploying remove "var/mfzdd-running.flag" file.')
                    .'</comment>');
                return;
            }

            $this->fileIo->write(Config::RUNNING_FLAG_FILE, '');

            $this->cliInput = $input;
            $this->cliOutput = $output;

            $this->dispatchEvent('mf_zdd_before_zdd_start');

            $setupStaticContent = true;
            $setupDi = true;
            $setupAll = true;

            $this->initDirectories();

            $this->removeOldFilesInMagentoRoot(true, true, true);

            $filename = BP . '/composer.json';
            $composerJson = $this->fileIo->read($filename);
            $composerData = json_decode($composerJson, true);

            if (!isset($composerData['config']['discard-changes']) ||
                $composerData['config']['discard-changes'] !== true
            ) {
                $composerData['config']['discard-changes'] = true;
                $this->fileIo->write(
                    $filename,
                    str_replace('\/', '/', json_encode($composerData, JSON_PRETTY_PRINT))
                );
            }

            $this->createInstanceFolder();

            $this->chdir($this->instanceDirectory);
            //$this->executeGitCommands();

            $this->executeComposerCommandsForPackage($commandType, $packageName);

            $enableNewModules = $this->enableNewModules();
            if ($enableNewModules) {
                $setupAll = true;
            }

            $this->setupAll = $setupAll;
            $this->setupDi = $setupDi;
            $this->setupStaticContent = $setupStaticContent;

            $this->executeSetupCommands($setupAll, $setupDi, $setupStaticContent);

            $this->chdir($this->magentoRootDirectory);
            $this->copyNewFilesToMagentoRoot($setupAll, $setupDi, $setupStaticContent);
            //$this->executeGitCommands();
            $this->copyNewComposerFilesToMagentoRoot();

            $composerOptions = explode(' ', $composerOptions);

            foreach ($composerOptions as $key => $option) {
                $option = explode('=', $option)[0];

                if (!in_array($option, $this->allowedOptionsForComposerInstall)) {
                    unset($composerOptions[$key]);
                }
            }

            $composerOptions = implode(' ', $composerOptions);

            $this->executeComposerCommands($composerOptions);
            $this->replaceFilesInMagentoRoot($setupAll, $setupDi, $setupStaticContent);

            /* Speed 2 seconds before working with caches */
            sleep(2);

            $this->chdir($this->magentoRootDirectory);
            if ($this->config->isEnableAllCaches()) {
                $this->execBinMagento('cache:enable');
            }

            $this->execteFinalCacheClean();

            /*
            if ($setupAll) {
                //$this->cliOutput->writeln(__('Disable maintanance mode'));
                $this->execBinMagento('maintenance:disable', true, false);
            }
            */

            $this->cliOutput->writeln(PHP_EOL . __('Deleting old files...'));

            $this->removeOldFilesInMagentoRoot($setupAll, $setupDi, $setupStaticContent);
            $this->deleteInstanceFolder();
            $this->cliOutput->writeln('<info>'.__('Update done!').'</info>');

            $this->dispatchEvent('mf_zdd_after_zdd_done');

            $this->fileIo->rm(Config::RUNNING_FLAG_FILE);
        }
    }

    /**
     * Execute Composer commands
     *
     * @param string $commandType
     * @param string $packageName
     * @return void
     */
    protected function executeComposerCommandsForPackage(string $commandType, string $packageName)
    {
        if ($this->config->isPullFromComposer()) {

            $this->dispatchEvent('mf_zdd_before_execute_composer_commands');

            /*$this->cliOutput->writeln(PHP_EOL . __('Executing composer install.'));*/
            if ($command = $this->config->getComposerCommand($commandType)) {
                $command = str_replace('{{magento-folder}}', $this->currentDirectory, $command);
                $command .= ' ' . $packageName;
                $this->exec($command);
            }

            $this->dispatchEvent('mf_zdd_after_execute_composer_commands');
        }
    }

    /**
     *  Copy composer.json & composer.lock files from instance to project root directory
     */
    public function copyNewComposerFilesToMagentoRoot()
    {
        $this->initDirectories();

        $this->cliOutput->writeln(PHP_EOL . '<info>'.__('Move processed composer.json & composer.lock files to Magento root directory...'). '</info>');

        $this->dispatchEvent('mf_zdd_before_copy_new_composer_files_to_magento_root');

        $copyFromInstance = ['composer.json', 'composer.lock'];

        foreach ($copyFromInstance as $item) {
            $this->move($this->instanceDirectory . '/' . $item, $this->magentoRootDirectory . '/' . $item);
        }

        $this->dispatchEvent('mf_zdd_after_copy_new_composer_files_to_magento_root');
    }
}
