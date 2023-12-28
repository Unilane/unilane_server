<?php
namespace Magento\AdobeStockImageAdminUi\Ui\Component\Listing\Columns\LicensedOverlay;

/**
 * Interceptor class for @see \Magento\AdobeStockImageAdminUi\Ui\Component\Listing\Columns\LicensedOverlay
 */
class Interceptor extends \Magento\AdobeStockImageAdminUi\Ui\Component\Listing\Columns\LicensedOverlay implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\UrlInterface $url, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $url, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare() : void
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'prepare');
        $pluginInfo ? $this->___callPlugins('prepare', func_get_args(), $pluginInfo) : parent::prepare();
    }
}
