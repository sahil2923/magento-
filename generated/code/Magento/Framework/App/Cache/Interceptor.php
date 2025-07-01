<?php
namespace Magento\Framework\App\Cache;

/**
 * Interceptor class for @see \Magento\Framework\App\Cache
 */
class Interceptor extends \Magento\Framework\App\Cache implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Cache\Frontend\Pool $frontendPool, $cacheIdentifier = null)
    {
        $this->___init();
        parent::__construct($frontendPool, $cacheIdentifier);
    }

    /**
     * {@inheritdoc}
     */
    public function clean($tags = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'clean');
        return $pluginInfo ? $this->___callPlugins('clean', func_get_args(), $pluginInfo) : parent::clean($tags);
    }
}
