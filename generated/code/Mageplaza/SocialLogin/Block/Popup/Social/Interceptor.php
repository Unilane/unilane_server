<?php
namespace Mageplaza\SocialLogin\Block\Popup\Social;

/**
 * Interceptor class for @see \Mageplaza\SocialLogin\Block\Popup\Social
 */
class Interceptor extends \Mageplaza\SocialLogin\Block\Popup\Social implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context, \Mageplaza\SocialLogin\Helper\Social $socialHelper, array $data = [])
    {
        $this->___init();
        parent::__construct($context, $socialHelper, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSocials()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getAvailableSocials');
        return $pluginInfo ? $this->___callPlugins('getAvailableSocials', func_get_args(), $pluginInfo) : parent::getAvailableSocials();
    }
}
