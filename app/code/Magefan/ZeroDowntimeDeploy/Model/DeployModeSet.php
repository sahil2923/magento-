<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\ZeroDowntimeDeploy\Model;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployModeSet extends Deploy
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit", '-1');

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
            $setupAll = false;

            $mode = $this->cliInput->getOption('mode');

            $this->initDirectories();

            $this->removeOldFilesInMagentoRoot(true, true, true);
            $this->createInstanceFolder();

            $this->chdir($this->instanceDirectory);
            /*
            $this->executeGitCommands();
            $this->executeComposerCommands();

            $enableNewModules = $this->enableNewModules();
            if ($enableNewModules) {
                $setupAll = true;
            }
            */

            $this->setupAll = $setupAll;
            $this->setupDi = $setupDi;
            $this->setupStaticContent = $setupStaticContent;

            $this->chdir($this->instanceDirectory);
            $this->execBinMagento('deploy:mode:set ' . $mode, true, false);

            $this->chdir($this->magentoRootDirectory);
            $this->copyNewFilesToMagentoRoot($setupAll, $setupDi, $setupStaticContent);
            /*
            $this->executeGitCommands();
            $this->executeComposerCommands();
            */

            $this->chdir($this->magentoRootDirectory);
            $this->execBinMagento('deploy:mode:set ' . $mode . '  --skip-compilation', true, false);

            $this->chdir($this->magentoRootDirectory);
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
}
