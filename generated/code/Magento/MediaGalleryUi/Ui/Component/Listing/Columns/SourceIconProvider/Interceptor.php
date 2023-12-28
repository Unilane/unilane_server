<?php
namespace Magento\MediaGalleryUi\Ui\Component\Listing\Columns\SourceIconProvider;

/**
 * Interceptor class for @see \Magento\MediaGalleryUi\Ui\Component\Listing\Columns\SourceIconProvider
 */
class Interceptor extends \Magento\MediaGalleryUi\Ui\Component\Listing\Columns\SourceIconProvider implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\View\Asset\Repository $assetRepository, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, array $components = [], array $data = [], array $sourceIcons = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $assetRepository, $scopeConfig, $components, $data, $sourceIcons);
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
