<?php
namespace Magento\Sales\Ui\Component\Listing\Column\Creditmemo\State;

/**
 * Interceptor class for @see \Magento\Sales\Ui\Component\Listing\Column\Creditmemo\State
 */
class Interceptor extends \Magento\Sales\Ui\Component\Listing\Column\Creditmemo\State implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $creditmemoRepository, $components, $data);
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
