<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Console\Command;

use Magefan\ZeroDowntimeDeploy\Model\Deploy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magefan\ZeroDowntimeDeploy\Model\Config;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Cron extends Command
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Deploy
     */
    private $deploy;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Filesystem|mixed
     */
    private $filesystem;

    /**
     * Cron constructor.
     * @param Config $config
     * @param Deploy $deploy
     * @param File $file
     * @param string|null $name
     * @param Filesystem|null $filesystem
     */
    public function __construct(
        Config $config,
        Deploy $deploy,
        File $file,
        string $name = null,
        Filesystem $filesystem = null
    ) {
        $this->config = $config;
        $this->deploy = $deploy;
        $this->file = $file;
        parent::__construct($name);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->filesystem = $filesystem ?: $objectManager->create(\Magento\Framework\Filesystem::class);
    }

    /**
     * @inheritDoc
     */
    public function configure()
    {
        $this->setName('magefan:zero-downtime:cron');
        $this->setDescription('Execute Cron');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->config->isEnabled()) {

            $directory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
            $rootPath = $directory->getAbsolutePath();

            if ($this->file->fileExists($rootPath . Config::RUNNING_FLAG_FILE)) {
                return 0;
            }
            if ($this->file->fileExists($rootPath . Config::SCHEDULED_FLAG_FILE)) {
                $this->file->rm(Config::SCHEDULED_FLAG_FILE);
                $this->deploy->execute($input, $output);
            }
            return 1;
        } else {
            $output->writeln('<comment>'
                . __(strrev('sdnammoC yolpeD nuR oT emitnwoD oreZ >- snoisnetxE nafegaM >- noitarugifnoC >-
                erotS nI noisnetxE elbanE esaelP'))
                .'</comment>');
        }

        return 0;
    }
}
