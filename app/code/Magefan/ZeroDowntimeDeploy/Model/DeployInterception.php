<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Symfony\Component\Console\Output\ConsoleOutput;

class DeployInterception
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @param Config $config
     * @param File $fileDriver
     * @param ConsoleOutput $output
     * @param Filesystem $filesystem
     */
    public function __construct(
        Config           $config,
        File             $fileDriver,
        ConsoleOutput    $output,
        Filesystem       $filesystem
    ) {
        $this->config = $config;
        $this->fileDriver = $fileDriver;
        $this->output = $output;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $command
     * @throws FileSystemException
     */
    public function execute(string $command)
    {
        if ($this->config->isEnabled() && $this->config->isDisableMagentoCommand()) {
            $flagFile = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->getAbsolutePath() . '/mfzdd-running.flag';
            if (!$this->fileDriver->isExists($flagFile)) {

                switch ($command) {
                    case 'deploy:mode:set':
                        $runCommand = 'magefan:zero-downtime:deploy-mode-set -m mode_name';
                        break;
                    case 'setup:upgrade':
                        $runCommand = 'magefan:zero-downtime:deploy';
                        break;
                    case 'setup:di:compile':
                        $runCommand = 'magefan:zero-downtime:deploy -d';
                        break;
                    case 'setup:static-content:deploy':
                        $runCommand = 'magefan:zero-downtime:deploy -s';
                        break;
                    default :
                        $runCommand =  'bin/magento magefan:zero-downtime';
                        break;
                }

                $this->output->writeln(
                    '<error>This command is not available. Please run: bin/magento ' . $runCommand  . PHP_EOL . PHP_EOL .
                    'To enable default Magento deploy commands run (but first, ask your manager): ' .
                    'bin/magento config:set mfzerodwt/general/disable_default_commands 0</error>'
                );
                exit(0);
            }
        }
    }
}
