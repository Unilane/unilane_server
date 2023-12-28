<?php
namespace Magento\InventoryAdminUi\Ui\Component\Listing\Stock\Column\EditAction;

/**
 * Interceptor class for @see \Magento\InventoryAdminUi\Ui\Component\Listing\Stock\Column\EditAction
 */
class Interceptor extends \Magento\InventoryAdminUi\Ui\Component\Listing\Stock\Column\EditAction implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\UrlInterface $urlBuilder, array $components = [], array $data = [], ?\Magento\Framework\AuthorizationInterface $authorization = null)
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $components, $data, $authorization);
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
