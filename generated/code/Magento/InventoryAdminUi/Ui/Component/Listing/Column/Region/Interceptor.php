<?php
namespace Magento\InventoryAdminUi\Ui\Component\Listing\Column\Region;

/**
 * Interceptor class for @see \Magento\InventoryAdminUi\Ui\Component\Listing\Column\Region
 */
class Interceptor extends \Magento\InventoryAdminUi\Ui\Component\Listing\Column\Region implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\InventoryAdminUi\Model\OptionSource\RegionSource $regionSource, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $regionSource, $components, $data);
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
