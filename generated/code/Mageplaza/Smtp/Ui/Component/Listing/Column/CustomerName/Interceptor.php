<?php
namespace Mageplaza\Smtp\Ui\Component\Listing\Column\CustomerName;

/**
 * Interceptor class for @see \Mageplaza\Smtp\Ui\Component\Listing\Column\CustomerName
 */
class Interceptor extends \Mageplaza\Smtp\Ui\Component\Listing\Column\CustomerName implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\UrlInterface $urlBuilder, \Magento\Quote\Model\QuoteFactory $quoteFactory, \Mageplaza\Smtp\Helper\EmailMarketing $helperEmailMarketing, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $quoteFactory, $helperEmailMarketing, $components, $data);
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
