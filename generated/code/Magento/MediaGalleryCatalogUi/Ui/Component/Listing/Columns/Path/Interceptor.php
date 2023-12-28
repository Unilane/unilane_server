<?php
namespace Magento\MediaGalleryCatalogUi\Ui\Component\Listing\Columns\Path;

/**
 * Interceptor class for @see \Magento\MediaGalleryCatalogUi\Ui\Component\Listing\Columns\Path
 */
class Interceptor extends \Magento\MediaGalleryCatalogUi\Ui\Component\Listing\Columns\Path implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $categoryRepository, $components, $data);
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
