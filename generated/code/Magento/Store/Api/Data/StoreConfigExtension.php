<?php
namespace Magento\Store\Api\Data;

/**
 * Extension class for @see \Magento\Store\Api\Data\StoreConfigInterface
 */
class StoreConfigExtension extends \Magento\Framework\Api\AbstractSimpleObject implements StoreConfigExtensionInterface
{
    /**
     * @return string|null
     */
    public function getPermissions()
    {
        return $this->_get('permissions');
    }

    /**
     * @param string $permissions
     * @return $this
     */
    public function setPermissions($permissions)
    {
        $this->setData('permissions', $permissions);
        return $this;
    }
}
