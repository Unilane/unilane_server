<?php
namespace Magento\Eav\Model\Attribute\Data\Select;

/**
 * Interceptor class for @see \Magento\Eav\Model\Attribute\Data\Select
 */
class Interceptor extends \Magento\Eav\Model\Attribute\Data\Select implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Locale\ResolverInterface $localeResolver)
    {
        $this->___init();
        parent::__construct($localeDate, $logger, $localeResolver);
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
