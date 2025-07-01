<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Console\Command;

use Symfony\Component\Console\Command\Command;
use Magefan\ZeroDowntimeDeploy\Model\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magefan\ZeroDowntimeDeploy\Model\Composer as ComposerAction;
use Symfony\Component\Console\Input\InputOption;

class Composer extends Command
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ComposerAction
     */
    protected $composerAction;

    public const COMPOSER_OPTIONS = 'options';

    /**
     * @param Config $config
     * @param composerAction $composerAction
     * @param string|null $name
     */
    public function __construct(
        Config $config,
        ComposerAction $composerAction,
        string $name = null
    ) {
        $this->config = $config;
        $this->composerAction = $composerAction;
        parent::__construct($name);
    }

    /**
     *  Add option to ClI command
     */
    public function configure()
    {
        $this->setName('magefan:zero-downtime:composer');
        $this->setDescription('Initiate composer process');
        $this->addArgument('commandType', InputArgument::OPTIONAL, '');
        $this->addArgument('value', InputArgument::OPTIONAL, '');
        $this->addOption(
            self::COMPOSER_OPTIONS,
            null,
            InputOption::VALUE_REQUIRED,
            'Composer Options'
        );

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
            if ($this->config->isPullFromComposer()) {
                $this->composerAction->execute($input, $output);
            } else {
                $output->writeln('<comment>'
                    . __(strrev('resopmoC morF lluP >- resopmoC >- emitnwoD oreZ >- snoisnetxE nafegaM >- noitarugifnoC >- serotS nI resopmoC elbanE esaelP'))
                    .'</comment>');
            }
        } else {
            $output->writeln('<comment>'
                . __(strrev('sdnammoC yolpeD nuR oT emitnwoD oreZ >- snoisnetxE nafegaM >- noitarugifnoC >- serotS nI noisnetxE elbanE esaelP'))
                .'</comment>');
        }

        return 0;
    }
}
