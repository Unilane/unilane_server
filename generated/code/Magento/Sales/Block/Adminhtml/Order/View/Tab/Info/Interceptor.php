<?php
namespace Magento\Sales\Block\Adminhtml\Order\View\Tab\Info;

/**
 * Interceptor class for @see \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info
 */
class Interceptor extends \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\Block\Template\Context $context, \Magento\Framework\Registry $registry, \Magento\Sales\Helper\Admin $adminHelper, array $data = [], ?\Magento\Shipping\Helper\Data $shippingHelper = null, ?\Magento\Tax\Helper\Data $taxHelper = null)
    {
        $this->___init();
        parent::__construct($context, $registry, $adminHelper, $data, $shippingHelper, $taxHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function getGiftOptionsHtml()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getGiftOptionsHtml');
        return $pluginInfo ? $this->___callPlugins('getGiftOptionsHtml', func_get_args(), $pluginInfo) : parent::getGiftOptionsHtml();
    }
}
