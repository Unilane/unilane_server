<?php
namespace AfterShip\Tracking\Controller\Protection\Adjust;

/**
 * Interceptor class for @see \AfterShip\Tracking\Controller\Protection\Adjust
 */
class Interceptor extends \AfterShip\Tracking\Controller\Protection\Adjust implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Checkout\Model\Cart $cart, \Magento\Framework\View\Asset\Repository $assetRepository, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Catalog\Api\Data\ProductInterfaceFactory $productInterfaceFactory, \Magento\Catalog\Api\ProductRepositoryInterface $productRepository, \Magento\Framework\App\Filesystem\DirectoryList $directoryList, \Magento\Framework\Filesystem\Io\File $file)
    {
        $this->___init();
        parent::__construct($context, $cart, $assetRepository, $storeManager, $productInterfaceFactory, $productRepository, $directoryList, $file);
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
