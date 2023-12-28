<?php
namespace Magento\InventoryAdminUi\Ui\Component\Listing\Column\EditAction;

/**
 * Interceptor class for @see \Magento\InventoryAdminUi\Ui\Component\Listing\Column\EditAction
 */
class Interceptor extends \Magento\InventoryAdminUi\Ui\Component\Listing\Column\EditAction implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\UrlInterface $urlBuilder, \Magento\Framework\AuthorizationInterface $authorization, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $authorization, $components, $data);
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
