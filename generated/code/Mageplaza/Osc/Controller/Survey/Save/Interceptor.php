<?php
namespace Mageplaza\Osc\Controller\Survey\Save;

/**
 * Interceptor class for @see \Mageplaza\Osc\Controller\Survey\Save
 */
class Interceptor extends \Mageplaza\Osc\Controller\Survey\Save implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Serialize\Serializer\Json $jsonHelper, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Sales\Model\Order $order, \Mageplaza\Osc\Helper\Data $oscHelper)
    {
        $this->___init();
        parent::__construct($context, $jsonHelper, $checkoutSession, $order, $oscHelper);
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
