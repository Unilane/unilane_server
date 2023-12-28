<?php
namespace Magento\InventoryShippingAdminUi\Ui\Component\Listing\Column\AllocatedSources;

/**
 * Interceptor class for @see \Magento\InventoryShippingAdminUi\Ui\Component\Listing\Column\AllocatedSources
 */
class Interceptor extends \Magento\InventoryShippingAdminUi\Ui\Component\Listing\Column\AllocatedSources implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\InventoryShippingAdminUi\Model\ResourceModel\GetAllocatedSourcesForOrder $getAllocatedSourcesForOrder, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $getAllocatedSourcesForOrder, $components, $data);
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
