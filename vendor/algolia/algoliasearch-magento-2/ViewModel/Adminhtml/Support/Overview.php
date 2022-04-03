<?php

namespace Algolia\AlgoliaSearch\ViewModel\Adminhtml\Support;

use Algolia\AlgoliaSearch\Helper\SupportHelper;
use Algolia\AlgoliaSearch\ViewModel\Adminhtml\BackendView;
use Magento\Backend\Block\Template;

class Overview implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /** @var BackendView */
    private $backendView;

    /** @var SupportHelper */
    private $supportHelper;

    /**
     * @param BackendView $backendView
     * @param SupportHelper $supportHelper
     */
    public function __construct(BackendView $backendView, SupportHelper $supportHelper)
    {
        $this->backendView = $backendView;
        $this->supportHelper = $supportHelper;
    }

    /** @return bool */
    public function isExtensionSupportEnabled()
    {
        return $this->supportHelper->isExtensionSupportEnabled();
    }

    /** @return string */
    public function getApplicationId()
    {
        return $this->supportHelper->getApplicationId();
    }

    /**
     * @return string
     */
    public function getLegacyVersionHtml()
    {
        /** @var Template $block */
        $block = $this->backendView->getLayout()->createBlock(Template::class);

        $block->setTemplate('Algolia_AlgoliaSearch::support/components/legacy-version.phtml');
        $block->setData('extension_version', $this->supportHelper->getExtensionVersion());

        return $block->toHtml();
    }
}
