<?php
namespace Openpay\Banks\Controller\Pse\Confirm;

/**
 * Interceptor class for @see \Openpay\Banks\Controller\Pse\Confirm
 */
class Interceptor extends \Openpay\Banks\Controller\Pse\Confirm implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\App\Request\Http $request, \Openpay\Banks\Model\Payment $payment, \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, \Magento\Checkout\Model\Session $checkoutSession, \Psr\Log\LoggerInterface $logger_interface, \Magento\Sales\Model\Service\InvoiceService $invoiceService, \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory, $request, $payment, $orderRepository, $checkoutSession, $logger_interface, $invoiceService, $transactionBuilder);
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
