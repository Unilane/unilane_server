<?php
namespace Magento\Catalog\Ui\Component\Listing\Columns\Websites;

/**
 * Interceptor class for @see \Magento\Catalog\Ui\Component\Listing\Columns\Websites
 */
class Interceptor extends \Magento\Catalog\Ui\Component\Listing\Columns\Websites implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, array $components = [], array $data = [], ?\Magento\Framework\DB\Helper $resourceHelper = null)
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $storeManager, $components, $data, $resourceHelper);
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
