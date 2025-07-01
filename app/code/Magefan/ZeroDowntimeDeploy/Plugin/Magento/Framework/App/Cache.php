<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Plugin\Magento\Framework\App;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Debug;
use Magefan\ZeroDowntimeDeploy\Model\DeployInterception;

/**
 * Plugin to prevent setup:di:compile
 */
class Cache
{
    const DI_COMPILE_CLASS = 'Magento\Setup\Console\Command\DiCompileCommand';

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
     * @param \Magento\Framework\App\Cache $subject
     * @param \Closure $proceed
     * @param array $tags
     * @return mixed
     * @throws FileSystemException
     */
    public function aroundClean(
        \Magento\Framework\App\Cache $subject,
        \Closure                     $proceed,
        $tags = []
    ) {
        if ($this->isDiCompileCommand()) {
            $this->deployInterception->execute('setup:di:compile');
        }

        return $proceed($tags);
    }

    /**
     * @return bool
     */
    protected function isDiCompileCommand()
    {
        $backtrace = Debug::backtrace(true, true, false);
        if (false != strpos($backtrace, self::DI_COMPILE_CLASS)) {
            return true;
        }

        return false;
    }
}
