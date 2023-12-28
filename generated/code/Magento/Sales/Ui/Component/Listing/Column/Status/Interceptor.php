<?php
namespace Magento\Sales\Ui\Component\Listing\Column\Status;

/**
 * Interceptor class for @see \Magento\Sales\Ui\Component\Listing\Column\Status
 */
class Interceptor extends \Magento\Sales\Ui\Component\Listing\Column\Status implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $collectionFactory, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $collectionFactory, $components, $data);
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
