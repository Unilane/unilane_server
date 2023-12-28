<?php
namespace Magento\AdobeStockImageAdminUi\Ui\Component\Listing\Columns\UnlicensedOverlay;

/**
 * Interceptor class for @see \Magento\AdobeStockImageAdminUi\Ui\Component\Listing\Columns\UnlicensedOverlay
 */
class Interceptor extends \Magento\AdobeStockImageAdminUi\Ui\Component\Listing\Columns\UnlicensedOverlay implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context, \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory, \Magento\AdobeStockAssetApi\Api\AssetRepositoryInterface $assetRepository, \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder, array $components = [], array $data = [])
    {
        $this->___init();
        parent::__construct($context, $uiComponentFactory, $assetRepository, $searchCriteriaBuilder, $components, $data);
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
