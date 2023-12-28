<?php
namespace Openpay\Cards\Controller\Payment\GetTypeCard;

/**
 * Interceptor class for @see \Openpay\Cards\Controller\Payment\GetTypeCard
 */
class Interceptor extends \Openpay\Cards\Controller\Payment\GetTypeCard implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Openpay\Cards\Model\Payment $payment, \Openpay\Cards\Logger\Logger $logger_interface, \Openpay\Cards\Model\Utils\OpenpayRequest $openpayRequest)
    {
        $this->___init();
        parent::__construct($context, $payment, $logger_interface, $openpayRequest);
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
