<?php
namespace Magento\Eav\Model\Attribute\Data\File;

/**
 * Interceptor class for @see \Magento\Eav\Model\Attribute\Data\File
 */
class Interceptor extends \Magento\Eav\Model\Attribute\Data\File implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Locale\ResolverInterface $localeResolver, \Magento\Framework\Url\EncoderInterface $urlEncoder, \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $fileValidator, \Magento\Framework\Filesystem $filesystem, \Magento\Framework\Filesystem\Io\File $fileIo)
    {
        $this->___init();
        parent::__construct($localeDate, $logger, $localeResolver, $urlEncoder, $fileValidator, $filesystem, $fileIo);
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
