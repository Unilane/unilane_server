<?php
namespace Icecat\DataFeed\Console\Command\AddQueueJobs;

/**
 * Interceptor class for @see \Icecat\DataFeed\Console\Command\AddQueueJobs
 */
class Interceptor extends \Icecat\DataFeed\Console\Command\AddQueueJobs implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Icecat\DataFeed\Helper\Data $data, \Magento\Framework\Message\ManagerInterface $messageManager, \Icecat\DataFeed\Model\Queue $queue, \Magento\Framework\App\State $state, ?string $name = null)
    {
        $this->___init();
        parent::__construct($data, $messageManager, $queue, $state, $name);
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
