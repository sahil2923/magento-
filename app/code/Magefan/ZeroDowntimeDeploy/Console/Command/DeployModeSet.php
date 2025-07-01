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
use Magefan\ZeroDowntimeDeploy\Model\DeployModeSet as DeployAction;

/**
 * Class Zero Deploy Command Line
 */
class DeployModeSet extends Command
{
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
     * @param DeployAction $deployAction
     * @param null $name
     */
    public function __construct(
        Config $config,
        DeployAction $deployAction,
        $name = null
    ) {
        $this->config = $config;
        $this->deployAction = $deployAction;
        parent::__construct($name);
    }

    /**
     *  Add option to ClI command
     */
    public function configure()
    {
        $commandOptions = [
            new InputOption(
                'mode',
                '-m',
                InputOption::VALUE_REQUIRED,
                'Set Default mode'
            )
        ];

        $this->setName('magefan:zero-downtime:deploy-mode-set');
        $this->setDescription('Initiate deploy process with deploy mode set command');
        $this->setDefinition($commandOptions);

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output):int
    {
        if ($this->config->isEnabled()) {
            $this->deployAction->execute($input, $output);
        } else {
            $output->writeln('<comment>'
                . __(strrev('sdnammoC yolpeD nuR oT emitnwoD oreZ >- snoisnetxE nafegaM >- noitarugifnoC >- serotS nI noisnetxE elbanE esaelP'))
                .'</comment>');
        }
        return 0;
    }
}
