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

class CopyNewFilesToMagentoRoot extends Command
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
     * Delete constructor.
     * @param Config $config
     * @param Deploy $deploy
     * @param string|null $name
     */
    public function __construct(
        Config $config,
        Deploy $deploy,
        string $name = null
    ) {
        $this->config = $config;
        $this->deploy = $deploy;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    public function configure()
    {
        $this->setName('magefan:zero-downtime:copy-new-files-in-magento-root');
        $this->setDescription('Copy New Files In Magento Root');

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
            $this->deploy->setCliInput($input);
            $this->deploy->setCliOutput($output);
            $this->deploy->copyNewFilesToMagentoRoot(true, true, true);
        } else {
            $output->writeln('<comment>'
                . __(strrev('sdnammoC yolpeD nuR oT emitnwoD oreZ >- snoisnetxE nafegaM >- noitarugifnoC >- erotS nI noisnetxE elbanE esaelP'))
                .'</comment>');
        }

        return 0;
    }
}
