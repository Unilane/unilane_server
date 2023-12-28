<?php
namespace Magento\MediaGalleryUi\Ui\Component\Listing\Columns\Url;

/**
 * Interceptor class for @see \Magento\MediaGalleryUi\Ui\Component\Listing\Columns\Url
 */
class Interceptor extends \Magento\MediaGalleryUi\Ui\Component\Listing\Columns\Url implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Backend\Model\UrlInterface $urlInterface, \Magento\Cms\Helper\Wysiwyg\Images $images, \Magento\Cms\Model\Wysiwyg\Images\Storage $storage, \Magento\Framework\AuthorizationInterface $authorization, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $storeManager, $urlInterface, $images, $storage, $authorization, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare() : void
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'prepare');
        $pluginInfo ? $this->___callPlugins('prepare', func_get_args(), $pluginInfo) : parent::prepare();
    }
}
