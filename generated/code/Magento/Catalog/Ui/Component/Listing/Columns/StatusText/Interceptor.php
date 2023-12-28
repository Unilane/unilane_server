<?php
namespace Magento\Catalog\Ui\Component\Listing\Columns\StatusText;

/**
 * Interceptor class for @see \Magento\Catalog\Ui\Component\Listing\Columns\StatusText
 */
class Interceptor extends \Magento\Catalog\Ui\Component\Listing\Columns\StatusText implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Catalog\Model\Product\Attribute\Source\Status $status, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $status, $components, $data);
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
