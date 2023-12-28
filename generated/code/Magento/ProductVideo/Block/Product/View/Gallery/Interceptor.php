<?php
namespace Magento\ProductVideo\Block\Product\View\Gallery;

/**
 * Interceptor class for @see \Magento\ProductVideo\Block\Product\View\Gallery
 */
class Interceptor extends \Magento\ProductVideo\Block\Product\View\Gallery implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Catalog\Block\Product\Context $context, \Magento\Framework\Stdlib\ArrayUtils $arrayUtils, \Magento\Framework\Json\EncoderInterface $jsonEncoder, \Magento\ProductVideo\Helper\Media $mediaHelper, array $data = [], ?\Magento\Catalog\Model\Product\Gallery\ImagesConfigFactoryInterface $imagesConfigFactory = null, array $galleryImagesConfig = [])
    {
        $this->___init();
        parent::__construct($context, $arrayUtils, $jsonEncoder, $mediaHelper, $data, $imagesConfigFactory, $galleryImagesConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsMediaGalleryDataJson()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getOptionsMediaGalleryDataJson');
        return $pluginInfo ? $this->___callPlugins('getOptionsMediaGalleryDataJson', func_get_args(), $pluginInfo) : parent::getOptionsMediaGalleryDataJson();
    }

    /**
     * {@inheritdoc}
     */
    public function getMagnifier()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMagnifier');
        return $pluginInfo ? $this->___callPlugins('getMagnifier', func_get_args(), $pluginInfo) : parent::getMagnifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isRedirectToCartEnabled()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isRedirectToCartEnabled');
        return $pluginInfo ? $this->___callPlugins('isRedirectToCartEnabled', func_get_args(), $pluginInfo) : parent::isRedirectToCartEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getImage');
        return $pluginInfo ? $this->___callPlugins('getImage', func_get_args(), $pluginInfo) : parent::getImage($product, $imageId, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getVar($name, $module = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getVar');
        return $pluginInfo ? $this->___callPlugins('getVar', func_get_args(), $pluginInfo) : parent::getVar($name, $module);
    }
}
