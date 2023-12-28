<?php
namespace Magento\Store\Model\Service\StoreConfigManager;

/**
 * Interceptor class for @see \Magento\Store\Model\Service\StoreConfigManager
 */
class Interceptor extends \Magento\Store\Model\Service\StoreConfigManager implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Store\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Store\Model\Data\StoreConfigFactory $storeConfigFactory)
    {
        $this->___init();
        parent::__construct($storeCollectionFactory, $scopeConfig, $storeConfigFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreConfigs(?array $storeCodes = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getStoreConfigs');
        return $pluginInfo ? $this->___callPlugins('getStoreConfigs', func_get_args(), $pluginInfo) : parent::getStoreConfigs($storeCodes);
    }
}
