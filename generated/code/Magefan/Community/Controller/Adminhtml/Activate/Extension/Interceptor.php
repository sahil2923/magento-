<?php
namespace Magefan\Community\Controller\Adminhtml\Activate\Extension;

/**
 * Interceptor class for @see \Magefan\Community\Controller\Adminhtml\Activate\Extension
 */
class Interceptor extends \Magefan\Community\Controller\Adminhtml\Activate\Extension implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\App\Config\Storage\WriterInterface $configWriter, \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList, \Magento\Framework\Stdlib\DateTime\DateTime $date)
    {
        $this->___init();
        parent::__construct($context, $configWriter, $cacheTypeList, $date);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        return $pluginInfo ? $this->___callPlugins('dispatch', func_get_args(), $pluginInfo) : parent::dispatch($request);
    }
}
