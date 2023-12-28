<?php
namespace Magento\Customer\Ui\Component\Listing\Column\GroupActions;

/**
 * Interceptor class for @see \Magento\Customer\Ui\Component\Listing\Column\GroupActions
 */
class Interceptor extends \Magento\Customer\Ui\Component\Listing\Column\GroupActions implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\UrlInterface $urlBuilder, \Magento\Framework\Escaper $escaper, array $components = [], array $data = [], ?\Magento\Customer\Api\GroupManagementInterface $groupManagement = null)
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $escaper, $components, $data, $groupManagement);
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
