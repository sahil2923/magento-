<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Rewrite\Magento\Deploy\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magefan\ZeroDowntimeDeploy\Model\DeployInterception;

/**
 * Plugin to prevent deploy:mode:set
 */
class SetModeCommand extends \Magento\Deploy\Console\Command\SetModeCommand
{
    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectManager->get(DeployInterception::class)->execute('deploy:mode:set');

        return parent::execute($input, $output);
    }
}
