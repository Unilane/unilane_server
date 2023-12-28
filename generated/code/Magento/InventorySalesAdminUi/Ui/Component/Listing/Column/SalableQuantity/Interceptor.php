<?php
namespace Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalableQuantity;

/**
 * Interceptor class for @see \Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalableQuantity
 */
class Interceptor extends \Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalableQuantity implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType, \Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface $isSingleSourceMode, \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku, \Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku $getAssignedStockIdsBySku, int $maximumStocksToShow, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $isSourceItemManagementAllowedForProductType, $isSingleSourceMode, $getSalableQuantityDataBySku, $getAssignedStockIdsBySku, $maximumStocksToShow, $components, $data);
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
