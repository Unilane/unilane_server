<?php
namespace Magento\Customer\Model\Attribute\Data\Postcode;

/**
 * Interceptor class for @see \Magento\Customer\Model\Attribute\Data\Postcode
 */
class Interceptor extends \Magento\Customer\Model\Attribute\Data\Postcode implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Locale\ResolverInterface $localeResolver, \Magento\Directory\Helper\Data $directoryHelper)
    {
        $this->___init();
        parent::__construct($localeDate, $logger, $localeResolver, $directoryHelper);
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
