<?php
namespace Icecat\DataFeed\Ui\Component\Listing\Columns\DeleteAttachment;

/**
 * Interceptor class for @see \Icecat\DataFeed\Ui\Component\Listing\Columns\DeleteAttachment
 */
class Interceptor extends \Icecat\DataFeed\Ui\Component\Listing\Columns\DeleteAttachment implements \Magento\Framework\Interception\InterceptorInterface
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
