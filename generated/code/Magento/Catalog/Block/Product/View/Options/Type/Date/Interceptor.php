<?php
namespace Magento\Catalog\Block\Product\View\Options\Type\Date;

/**
 * Interceptor class for @see \Magento\Catalog\Block\Product\View\Options\Type\Date
 */
class Interceptor extends \Magento\Catalog\Block\Product\View\Options\Type\Date implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context, \Magento\Framework\Pricing\Helper\Data $pricingHelper, \Magento\Catalog\Helper\Data $catalogData, \Magento\Catalog\Model\Product\Option\Type\Date $catalogProductOptionTypeDate, array $data = [], ?\Magento\Framework\Data\Form\FilterFactory $filterFactory = null)
    {
        $this->___init();
        parent::__construct($context, $pricingHelper, $catalogData, $catalogProductOptionTypeDate, $data, $filterFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getOption');
        return $pluginInfo ? $this->___callPlugins('getOption', func_get_args(), $pluginInfo) : parent::getOption();
    }
}
