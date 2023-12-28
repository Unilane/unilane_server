<?php
namespace Magento\ConfigurableProduct\Ui\Component\Listing\AssociatedProduct\Columns\Price;

/**
 * Interceptor class for @see \Magento\ConfigurableProduct\Ui\Component\Listing\AssociatedProduct\Columns\Price
 */
class Interceptor extends \Magento\ConfigurableProduct\Ui\Component\Listing\AssociatedProduct\Columns\Price implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\Locale\CurrencyInterface $localeCurrency, \Magento\Store\Model\StoreManagerInterface $storeManager, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $localeCurrency, $storeManager, $components, $data);
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
