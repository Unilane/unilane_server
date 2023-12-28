<?php
namespace Mageplaza\Smtp\Ui\Component\Listing\Column\Actions;

/**
 * Interceptor class for @see \Mageplaza\Smtp\Ui\Component\Listing\Column\Actions
 */
class Interceptor extends \Mageplaza\Smtp\Ui\Component\Listing\Column\Actions implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\UrlInterface $urlBuilder, \Mageplaza\Smtp\Helper\Data $helperData, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $helperData, $components, $data);
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
