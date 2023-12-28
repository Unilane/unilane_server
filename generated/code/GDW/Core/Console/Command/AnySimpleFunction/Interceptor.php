<?php
namespace GDW\Core\Console\Command\AnySimpleFunction;

/**
 * Interceptor class for @see \GDW\Core\Console\Command\AnySimpleFunction
 */
class Interceptor extends \GDW\Core\Console\Command\AnySimpleFunction implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\State $state, \GDW\Core\Helper\Data $helperData, $name = null)
    {
        $this->___init();
        parent::__construct($state, $helperData, $name);
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
