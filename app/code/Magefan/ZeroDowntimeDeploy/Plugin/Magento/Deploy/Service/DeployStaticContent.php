<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Plugin\Magento\Deploy\Service;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Debug;
use Magefan\ZeroDowntimeDeploy\Model\DeployInterception;

/**
 * Plugin to prevent setup:static-content:deploy
 */
class DeployStaticContent
{
    const DEPLOY_CLASS = 'Magento\Setup\Console\Command\DeployStaticContentCommand';

    /**
     * @var DeployInterception
     */
    private $deployInterception;

    /**
     * @param DeployInterception $deployInterception
     */
    public function __construct(
        DeployInterception  $deployInterception
    ) {
        $this->deployInterception = $deployInterception;
    }

    /**
     * @param \Magento\Deploy\Service\DeployStaticContent $subject
     * @param \Closure $proceed
     * @param $options
     * @return mixed
     * @throws FileSystemException
     */
    public function aroundDeploy(
        \Magento\Deploy\Service\DeployStaticContent $subject,
        \Closure                                    $proceed,
        $options
    ) {
        if ($this->isDeployCommand()) {
            $this->deployInterception->execute('setup:static-content:deploy');
        }

        return $proceed($options);
    }

    /**
     * @return bool
     */
    protected function isDeployCommand()
    {
        $backtrace = Debug::backtrace(true, true, false);
        if (false != strpos($backtrace, self::DEPLOY_CLASS)) {
            return true;
        }

        return false;
    }
}
