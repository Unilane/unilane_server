<?php
namespace Magento\AsynchronousOperations\Ui\Component\Listing\Column\NotificationDismissActions;

/**
 * Interceptor class for @see \Magento\AsynchronousOperations\Ui\Component\Listing\Column\NotificationDismissActions
 */
class Interceptor extends \Magento\AsynchronousOperations\Ui\Component\Listing\Column\NotificationDismissActions implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
