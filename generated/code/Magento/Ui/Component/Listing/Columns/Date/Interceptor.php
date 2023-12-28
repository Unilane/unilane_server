<?php
namespace Magento\Ui\Component\Listing\Columns\Date;

/**
 * Interceptor class for @see \Magento\Ui\Component\Listing\Columns\Date
 */
class Interceptor extends \Magento\Ui\Component\Listing\Columns\Date implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone, \Magento\Framework\Stdlib\BooleanUtils $booleanUtils, array $components = [], array $data = [], ?\Magento\Framework\Locale\ResolverInterface $localeResolver = null, ?\Magento\Framework\Locale\Bundle\DataBundle $dataBundle = null)
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $timezone, $booleanUtils, $components, $data, $localeResolver, $dataBundle);
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
