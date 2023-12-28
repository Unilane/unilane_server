<?php
namespace Magento\Checkout\Helper\Data;

/**
 * Interceptor class for @see \Magento\Checkout\Helper\Data
 */
class Interceptor extends \Magento\Checkout\Helper\Data implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Helper\Context $context, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate, \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder, \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation, \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency, ?\Magento\Sales\Api\PaymentFailuresInterface $paymentFailures = null)
    {
        $this->___init();
        parent::__construct($context, $storeManager, $checkoutSession, $localeDate, $transportBuilder, $inlineTranslation, $priceCurrency, $paymentFailures);
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowedGuestCheckout(\Magento\Quote\Model\Quote $quote, $store = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isAllowedGuestCheckout');
        return $pluginInfo ? $this->___callPlugins('isAllowedGuestCheckout', func_get_args(), $pluginInfo) : parent::isAllowedGuestCheckout($quote, $store);
    }
}
