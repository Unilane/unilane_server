<?php
namespace Mageplaza\SocialLogin\Controller\Popup\Create;

/**
 * Interceptor class for @see \Mageplaza\SocialLogin\Controller\Popup\Create
 */
class Interceptor extends \Mageplaza\SocialLogin\Controller\Popup\Create implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Customer\Model\Session $customerSession, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Customer\Api\AccountManagementInterface $accountManagement, \Magento\Customer\Helper\Address $addressHelper, \Magento\Framework\UrlFactory $urlFactory, \Magento\Customer\Model\Metadata\FormFactory $formFactory, \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory, \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory, \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory, \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory, \Magento\Customer\Model\Url $customerUrl, \Magento\Customer\Model\Registration $registration, \Magento\Framework\Escaper $escaper, \Magento\Customer\Model\CustomerExtractor $customerExtractor, \Magento\Framework\Api\DataObjectHelper $dataObjectHelper, \Magento\Customer\Model\Account\Redirect $accountRedirect, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, \Magento\Framework\Controller\Result\JsonFactory $jsonFactory, ?\Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator = null)
    {
        $this->___init();
        parent::__construct($context, $customerSession, $scopeConfig, $storeManager, $accountManagement, $addressHelper, $urlFactory, $formFactory, $subscriberFactory, $regionDataFactory, $addressDataFactory, $customerDataFactory, $customerUrl, $registration, $escaper, $customerExtractor, $dataObjectHelper, $accountRedirect, $customerRepository, $jsonFactory, $formKeyValidator);
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
