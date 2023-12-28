<?php
namespace Unilane\SyncProducts\Controller\Adminhtml\Products\UpdateStock;

/**
 * Interceptor class for @see \Unilane\SyncProducts\Controller\Adminhtml\Products\UpdateStock
 */
class Interceptor extends \Unilane\SyncProducts\Controller\Adminhtml\Products\UpdateStock implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Model\ProductFactory $productFactory, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Catalog\Api\ProductRepositoryInterface $productRepository)
    {
        $this->___init();
        parent::__construct($context, $productFactory, $resultJsonFactory, $productRepository);
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
