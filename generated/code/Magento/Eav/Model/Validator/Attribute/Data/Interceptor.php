<?php
namespace Magento\Eav\Model\Validator\Attribute\Data;

/**
 * Interceptor class for @see \Magento\Eav\Model\Validator\Attribute\Data
 */
class Interceptor extends \Magento\Eav\Model\Validator\Attribute\Data implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Eav\Model\AttributeDataFactory $attrDataFactory, array $ignoredAttributesByTypesList = [])
    {
        $this->___init();
        parent::__construct($attrDataFactory, $ignoredAttributesByTypesList);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($entity)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isValid');
        return $pluginInfo ? $this->___callPlugins('isValid', func_get_args(), $pluginInfo) : parent::isValid($entity);
    }
}
