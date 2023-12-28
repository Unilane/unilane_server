<?php
namespace Icecat\DataFeed\Controller\Adminhtml\Data\Importproductinfo;

/**
 * Interceptor class for @see \Icecat\DataFeed\Controller\Adminhtml\Data\Importproductinfo
 */
class Interceptor extends \Icecat\DataFeed\Controller\Adminhtml\Data\Importproductinfo implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Icecat\DataFeed\Model\Scheduler $scheduler)
    {
        $this->___init();
        parent::__construct($context, $scheduler);
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
