<?php
namespace Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalesChannels;

/**
 * Interceptor class for @see \Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalesChannels
 */
class Interceptor extends \Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalesChannels implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\InventorySalesAdminUi\Model\SalesChannelNameResolverInterface $salesChannelNameResolver, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $salesChannelNameResolver, $components, $data);
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
