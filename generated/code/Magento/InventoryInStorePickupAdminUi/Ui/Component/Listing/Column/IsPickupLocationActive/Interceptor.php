<?php
namespace Magento\InventoryInStorePickupAdminUi\Ui\Component\Listing\Column\IsPickupLocationActive;

/**
 * Interceptor class for @see \Magento\InventoryInStorePickupAdminUi\Ui\Component\Listing\Column\IsPickupLocationActive
 */
class Interceptor extends \Magento\InventoryInStorePickupAdminUi\Ui\Component\Listing\Column\IsPickupLocationActive implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
