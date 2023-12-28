<?php
namespace Magento\MediaGalleryCatalogUi\Ui\Component\Listing\Columns\Thumbnail;

/**
 * Interceptor class for @see \Magento\MediaGalleryCatalogUi\Ui\Component\Listing\Columns\Thumbnail
 */
class Interceptor extends \Magento\MediaGalleryCatalogUi\Ui\Component\Listing\Columns\Thumbnail implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Catalog\Model\Category\Image $categoryImage, \Magento\Catalog\Model\CategoryRepository $categoryRepository, \Magento\Framework\View\Asset\Repository $assetRepository, array $defaultPlaceholder = [], array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $storeManager, $categoryImage, $categoryRepository, $assetRepository, $defaultPlaceholder, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'prepare');
        return $pluginInfo ? $this->___callPlugins('prepare', func_get_args(), $pluginInfo) : parent::prepare();
    }
}
