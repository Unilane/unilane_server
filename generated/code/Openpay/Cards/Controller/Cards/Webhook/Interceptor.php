<?php
namespace Openpay\Cards\Controller\Cards\Webhook;

/**
 * Interceptor class for @see \Openpay\Cards\Controller\Cards\Webhook
 */
class Interceptor extends \Openpay\Cards\Controller\Cards\Webhook implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\App\Request\Http $request, \Openpay\Cards\Model\Payment $payment, \Openpay\Cards\Logger\Logger $logger_interface, \Magento\Sales\Model\Service\InvoiceService $invoiceService, \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository, \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        $this->___init();
        parent::__construct($context, $request, $payment, $logger_interface, $invoiceService, $transactionRepository, $searchCriteriaBuilder);
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
