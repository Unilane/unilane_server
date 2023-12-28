<?php
namespace Mageplaza\Osc\Controller\Index\Index;

/**
 * Interceptor class for @see \Mageplaza\Osc\Controller\Index\Index
 */
class Interceptor extends \Mageplaza\Osc\Controller\Index\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Customer\Model\Session $customerSession, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, \Magento\Customer\Api\AccountManagementInterface $accountManagement, \Magento\Framework\Registry $coreRegistry, \Magento\Framework\Translate\InlineInterface $translateInline, \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\View\LayoutFactory $layoutFactory, \Magento\Quote\Api\CartRepositoryInterface $quoteRepository, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory, \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Catalog\Model\ProductRepository $productRepository, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Checkout\Model\Cart $cart, \Psr\Log\LoggerInterface $logger, \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable, \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector, \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagement, \Magento\Checkout\Model\Session $checkoutSession, \Mageplaza\Osc\Helper\Data $helper)
    {
        $this->___init();
        parent::__construct($context, $customerSession, $customerRepository, $accountManagement, $coreRegistry, $translateInline, $formKeyValidator, $scopeConfig, $layoutFactory, $quoteRepository, $resultPageFactory, $resultLayoutFactory, $resultRawFactory, $resultJsonFactory, $productRepository, $storeManager, $cart, $logger, $configurable, $totalsCollector, $shippingMethodManagement, $checkoutSession, $helper);
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
