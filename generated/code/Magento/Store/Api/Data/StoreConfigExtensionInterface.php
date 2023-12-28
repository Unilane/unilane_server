<?php
namespace Magento\Store\Api\Data;

/**
 * ExtensionInterface class for @see \Magento\Store\Api\Data\StoreConfigInterface
 */
interface StoreConfigExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * @return string|null
     */
    public function getPermissions();

    /**
     * @param string $permissions
     * @return $this
     */
    public function setPermissions($permissions);
}
