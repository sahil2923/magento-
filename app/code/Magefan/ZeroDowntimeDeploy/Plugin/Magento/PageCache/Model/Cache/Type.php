<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Plugin\Magento\PageCache\Model\Cache;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Debug;
use Magefan\ZeroDowntimeDeploy\Model\DeployInterception;

/**
 * Plugin to prevent setup:upgrade
 */
class Type
{
    const UPGRADE_CLASS = 'Magento\Setup\Console\Command\UpgradeCommand';

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
     * @param \Magento\PageCache\Model\Cache\Type $subject
     * @param \Closure $proceed
     * @param string $mode
     * @param array $tags
     * @return mixed
     * @throws FileSystemException
     */
    public function aroundClean(
        \Magento\PageCache\Model\Cache\Type $subject,
        \Closure                            $proceed,
        $mode = \Zend_Cache::CLEANING_MODE_ALL,
        $tags = []
    ) {
        if ($this->isUpgradeCommand()) {
            $this->deployInterception->execute('setup:upgrade');
        }

        return $proceed($mode, $tags);
    }

    /**
     * @return bool
     */
    protected function isUpgradeCommand()
    {
        $backtrace = Debug::backtrace(true, true, false);
        if (false != strpos($backtrace, self::UPGRADE_CLASS)) {
            return true;
        }

        return false;
    }
}
