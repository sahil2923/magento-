<?php
namespace Magento\Framework\Module\Plugin\DbStatusValidator;

/**
 * Interceptor class for @see \Magento\Framework\Module\Plugin\DbStatusValidator
 */
class Interceptor extends \Magento\Framework\Module\Plugin\DbStatusValidator implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Cache\FrontendInterface $cache, \Magento\Framework\Module\DbVersionInfo $dbVersionInfo, \Magento\Framework\App\DeploymentConfig $deploymentConfig)
    {
        $this->___init();
        parent::__construct($cache, $dbVersionInfo, $deploymentConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeDispatch(\Magento\Framework\App\FrontController $subject, \Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'beforeDispatch');
        return $pluginInfo ? $this->___callPlugins('beforeDispatch', func_get_args(), $pluginInfo) : parent::beforeDispatch($subject, $request);
    }
}
