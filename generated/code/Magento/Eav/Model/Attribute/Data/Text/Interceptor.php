<?php
namespace Magento\Eav\Model\Attribute\Data\Text;

/**
 * Interceptor class for @see \Magento\Eav\Model\Attribute\Data\Text
 */
class Interceptor extends \Magento\Eav\Model\Attribute\Data\Text implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Locale\ResolverInterface $localeResolver, \Magento\Framework\Stdlib\StringUtils $stringHelper)
    {
        $this->___init();
        parent::__construct($localeDate, $logger, $localeResolver, $stringHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'validateValue');
        return $pluginInfo ? $this->___callPlugins('validateValue', func_get_args(), $pluginInfo) : parent::validateValue($value);
    }
}
