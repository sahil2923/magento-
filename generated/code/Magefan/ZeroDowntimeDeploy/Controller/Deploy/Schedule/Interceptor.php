<?php
namespace Magefan\ZeroDowntimeDeploy\Controller\Deploy\Schedule;

/**
 * Interceptor class for @see \Magefan\ZeroDowntimeDeploy\Controller\Deploy\Schedule
 */
class Interceptor extends \Magefan\ZeroDowntimeDeploy\Controller\Deploy\Schedule implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magefan\ZeroDowntimeDeploy\Model\Config $config, \Magento\Framework\Filesystem\Io\File $file, ?\Magento\Framework\Filesystem $filesystem = null)
    {
        $this->___init();
        parent::__construct($context, $config, $file, $filesystem);
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
