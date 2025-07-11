<?php
namespace Magefan\ZeroDowntimeDeploy\Rewrite\Magento\Deploy\Console\Command\SetModeCommand;

/**
 * Interceptor class for @see \Magefan\ZeroDowntimeDeploy\Rewrite\Magento\Deploy\Console\Command\SetModeCommand
 */
class Interceptor extends \Magefan\ZeroDowntimeDeploy\Rewrite\Magento\Deploy\Console\Command\SetModeCommand implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->___init();
        parent::__construct($objectManager);
    }

    /**
     * {@inheritdoc}
     */
    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'run');
        return $pluginInfo ? $this->___callPlugins('run', func_get_args(), $pluginInfo) : parent::run($input, $output);
    }
}
