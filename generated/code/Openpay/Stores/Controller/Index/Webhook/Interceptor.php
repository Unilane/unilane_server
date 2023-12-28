<?php
namespace Openpay\Stores\Controller\Index\Webhook;

/**
 * Interceptor class for @see \Openpay\Stores\Controller\Index\Webhook
 */
class Interceptor extends \Openpay\Stores\Controller\Index\Webhook implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\App\Request\Http $request, \Openpay\Stores\Model\Payment $payment, \Psr\Log\LoggerInterface $logger_interface, \Magento\Sales\Model\Service\InvoiceService $invoiceService)
    {
        $this->___init();
        parent::__construct($context, $request, $payment, $logger_interface, $invoiceService);
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
