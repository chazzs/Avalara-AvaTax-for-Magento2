<?php

namespace ClassyLlama\AvaTax\Controller\Adminhtml\Queue;

use ClassyLlama\AvaTax\Controller\Adminhtml\Queue;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Process extends Queue
{
    /**
     * Log page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var Page $pageResult */
        $pageResult = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $pageResult->setActiveMenu('ClassyLlama_AvaTax::avatax_queue');
        $pageResult->getConfig()->getTitle()->prepend(__('AvaTax Queue'));
        return $pageResult;
    }
}
