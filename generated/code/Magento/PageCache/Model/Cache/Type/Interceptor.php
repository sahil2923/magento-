<?php
namespace Magento\PageCache\Model\Cache\Type;

/**
 * Interceptor class for @see \Magento\PageCache\Model\Cache\Type
 */
class Interceptor extends \Magento\PageCache\Model\Cache\Type implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool, \Magento\Framework\Event\ManagerInterface $eventManager)
    {
        $this->___init();
        parent::__construct($cacheFrontendPool, $eventManager);
    }

    /**
     * {@inheritdoc}
     */
    public function clean($mode = 'all', array $tags = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'clean');
        return $pluginInfo ? $this->___callPlugins('clean', func_get_args(), $pluginInfo) : parent::clean($mode, $tags);
    }
}
