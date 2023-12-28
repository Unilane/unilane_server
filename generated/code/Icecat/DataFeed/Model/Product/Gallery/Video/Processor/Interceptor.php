<?php
namespace Icecat\DataFeed\Model\Product\Gallery\Video\Processor;

/**
 * Interceptor class for @see \Icecat\DataFeed\Model\Product\Gallery\Video\Processor
 */
class Interceptor extends \Icecat\DataFeed\Model\Product\Gallery\Video\Processor implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository, \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb, \Magento\Catalog\Model\Product\Media\Config $mediaConfig, \Magento\Framework\Filesystem $filesystem, \Magento\Catalog\Model\ResourceModel\Product\Gallery $resourceModel, \Magento\Catalog\Model\Product\Gallery\CreateHandler $createHandler, \Magento\Framework\App\Filesystem\DirectoryList $directoryList, \Magento\Framework\Filesystem\Io\File $file, \Magento\Catalog\Model\Product $product, ?\Magento\Framework\File\Mime $mime = null)
    {
        $this->___init();
        parent::__construct($attributeRepository, $fileStorageDb, $mediaConfig, $filesystem, $resourceModel, $createHandler, $directoryList, $file, $product, $mime);
    }

    /**
     * {@inheritdoc}
     */
    public function removeImage(\Magento\Catalog\Model\Product $product, $file)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'removeImage');
        return $pluginInfo ? $this->___callPlugins('removeImage', func_get_args(), $pluginInfo) : parent::removeImage($product, $file);
    }
}
