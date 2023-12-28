<?php
namespace Icecat\DataFeed\Model\Config\Backend\Serialized\ArraySerialized;

/**
 * Interceptor class for @see \Icecat\DataFeed\Model\Config\Backend\Serialized\ArraySerialized
 */
class Interceptor extends \Icecat\DataFeed\Model\Config\Backend\Serialized\ArraySerialized implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\App\Config\ScopeConfigInterface $config, \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList, ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource, ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection, \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository, \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory, \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup, array $data = [], ?\Magento\Framework\Serialize\Serializer\Json $serializer = null)
    {
        $this->___init();
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $attributeRepository, $eavSetupFactory, $moduleDataSetup, $data, $serializer);
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'afterSave');
        return $pluginInfo ? $this->___callPlugins('afterSave', func_get_args(), $pluginInfo) : parent::afterSave();
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'save');
        return $pluginInfo ? $this->___callPlugins('save', func_get_args(), $pluginInfo) : parent::save();
    }
}
