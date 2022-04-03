<?php

namespace Algolia\AlgoliaSearch\Controller\Adminhtml\Support;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

abstract class AbstractAction extends \Magento\Backend\App\Action
{
    /** @var ResultFactory */
    protected $resultFactory;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->resultFactory = $context->getResultFactory();
    }

    /** @return bool */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Algolia_AlgoliaSearch::manage');
    }
}
