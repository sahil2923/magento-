<?php
namespace Magefan\ZeroDowntimeDeploy\Console\Command\Cron;

/**
 * Interceptor class for @see \Magefan\ZeroDowntimeDeploy\Console\Command\Cron
 */
class Interceptor extends \Magefan\ZeroDowntimeDeploy\Console\Command\Cron implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magefan\ZeroDowntimeDeploy\Model\Config $config, \Magefan\ZeroDowntimeDeploy\Model\Deploy $deploy, \Magento\Framework\Filesystem\Io\File $file, ?string $name = null, ?\Magento\Framework\Filesystem $filesystem = null)
    {
        $this->___init();
        parent::__construct($config, $deploy, $file, $name, $filesystem);
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
