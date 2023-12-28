<?php
namespace Icecat\DataFeed\Controller\Adminhtml\Index\ProductData;

/**
 * Interceptor class for @see \Icecat\DataFeed\Controller\Adminhtml\Index\ProductData
 */
class Interceptor extends \Icecat\DataFeed\Controller\Adminhtml\Index\ProductData implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Icecat\DataFeed\Helper\Data $data, \Icecat\DataFeed\Service\IcecatApiService $icecatApiService, \Magento\Catalog\Model\ProductRepository $productRepository, \Icecat\DataFeed\Model\IceCatUpdateProduct $iceCatUpdateProduct, \Magento\Store\Api\StoreRepositoryInterface $storeRepository, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config, \Magento\Catalog\Model\Product\Gallery\Processor $processor, \Magento\Framework\App\ResourceConnection $resourceConnection, \Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->___init();
        parent::__construct($context, $data, $icecatApiService, $productRepository, $iceCatUpdateProduct, $storeRepository, $storeManager, $scopeConfig, $config, $processor, $resourceConnection, $objectManager);
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
