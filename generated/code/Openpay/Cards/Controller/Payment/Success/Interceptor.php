<?php
namespace Openpay\Cards\Controller\Payment\Success;

/**
 * Interceptor class for @see \Openpay\Cards\Controller\Payment\Success
 */
class Interceptor extends \Openpay\Cards\Controller\Payment\Success implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\App\Request\Http $request, \Openpay\Cards\Model\Payment $payment, \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, \Magento\Checkout\Model\Session $checkoutSession, \Openpay\Cards\Logger\Logger $logger_interface, \Magento\Sales\Model\Service\InvoiceService $invoiceService, \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder, \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender, \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender, \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository, \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder, \Magento\Framework\Registry $coreRegistry, \Magento\Quote\Api\CartRepositoryInterface $quoteRepository, \Magento\Framework\Message\ManagerInterface $messageManager)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory, $request, $payment, $orderRepository, $checkoutSession, $logger_interface, $invoiceService, $transactionBuilder, $orderSender, $invoiceSender, $transactionRepository, $searchCriteriaBuilder, $coreRegistry, $quoteRepository, $messageManager);
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
