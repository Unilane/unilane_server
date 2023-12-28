<?php
namespace Magento\ImportExport\Ui\Component\Columns\FileDisplayName;

/**
 * Interceptor class for @see \Magento\ImportExport\Ui\Component\Columns\FileDisplayName
 */
class Interceptor extends \Magento\ImportExport\Ui\Component\Columns\FileDisplayName implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\ImportExport\Model\LocalizedFileName $localizedFileName, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $localizedFileName, $components, $data);
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
