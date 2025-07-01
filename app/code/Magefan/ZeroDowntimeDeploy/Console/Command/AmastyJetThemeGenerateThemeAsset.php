<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magefan\ZeroDowntimeDeploy\Model\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

class AmastyJetThemeGenerateThemeAsset extends Command
{
    /**
     * @var AssetManager
     */
    private $assetManager;

    /**
     * @var State
     */
    private $state;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var DeployAction
     */
    protected $deployAction;

    /**
     * Deploy constructor.
     * @param Config $config
     * @param State $state
     * @param null $name
     */
    public function __construct(
        Config $config,
        State $state,
        $name = null
    ) {
        $this->config = $config;
        $this->state = $state;
        parent::__construct($name);
    }

    /**
     *  Add option to ClI command
     */
    public function configure()
    {
        $this->setName('magefan:zero-downtime:amasty-jet-theme-generate-theme-asset');
        $this->setDescription('Generate Amasty Jet Theme Theme Asset');
        //$this->setDefinition($commandOptions);

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->config->isEnabled()) {
            if ($this->getAssetManager()) {
                $this->state->emulateAreaCode(
                    Area::AREA_FRONTEND,
                    [$this->getAssetManager(), 'generate']
                );
            }
        } else {
            $output->writeln('<comment>'
                . __(strrev('sdnammoC yolpeD nuR oT emitnwoD oreZ >- snoisnetxE nafegaM >- noitarugifnoC >- serotS nI noisnetxE elbanE esaelP'))
                .'</comment>');
        }
        return 0;
    }

    /**
     * @return mixin
     */
    private function getAssetManager()
    {
        if (null === $this->assetManager) {
            try {
                $this->assetManager = \Magento\Framework\App\ObjectManager::getInstance()->get(\Amasty\JetTheme\Model\AssetManager::class);
            } catch (\Exception $e) {
                $this->assetManager = false;
            }
        }

        return $this->assetManager;
    }
}
