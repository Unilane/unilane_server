<?php
namespace Icecat\DataFeed\Controller\Adminhtml\Index\DeleteReview;

/**
 * Interceptor class for @see \Icecat\DataFeed\Controller\Adminhtml\Index\DeleteReview
 */
class Interceptor extends \Icecat\DataFeed\Controller\Adminhtml\Index\DeleteReview implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Icecat\DataFeed\Helper\Data $data, \Icecat\DataFeed\Service\IcecatApiService $icecatApiService, \Magento\Catalog\Model\ProductRepository $productRepository, \Icecat\DataFeed\Model\IceCatUpdateProduct $iceCatUpdateProduct, \Magento\Store\Api\StoreRepositoryInterface $storeRepository, \Icecat\DataFeed\Model\ProductReviewFactory $productReview)
    {
        $this->___init();
        parent::__construct($context, $data, $icecatApiService, $productRepository, $iceCatUpdateProduct, $storeRepository, $productReview);
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
