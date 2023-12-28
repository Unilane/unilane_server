<?php
namespace Magento\Sales\Ui\Component\Listing\Column\Price;

/**
 * Interceptor class for @see \Magento\Sales\Ui\Component\Listing\Column\Price
 */
class Interceptor extends \Magento\Sales\Ui\Component\Listing\Column\Price implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter, array $components = [], array $data = [], ?\Magento\Directory\Model\Currency $currency = null, ?\Magento\Store\Model\StoreManagerInterface $storeManager = null)
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $priceFormatter, $components, $data, $currency, $storeManager);
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
