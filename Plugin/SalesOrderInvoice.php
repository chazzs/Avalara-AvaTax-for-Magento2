<?php

namespace ClassyLlama\AvaTax\Plugin;

use ClassyLlama\AvaTax\Model\Config;
use ClassyLlama\AvaTax\Model\QueueFactory;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SalesOrderInvoice
{
    const XML_PATH_AVATAX_QUEUE_SUBMIT_ENABLED = 'tax/avatax/enabled';

    /**
     * @var Config
     */
    protected $avaTaxConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $avaTaxConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $avaTaxConfig,
        QueueFactory $queueFactory,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->avaTaxConfig = $avaTaxConfig;
        $this->queueFactory = $queueFactory;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param null $store
     * @return bool
     */
    public function queueSubmitEnabled($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_AVATAX_QUEUE_SUBMIT_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }





    /**
     * @param \Magento\Sales\Model\Order\Invoice $subject
     * @param \Magento\Sales\Model\Order\Invoice $result
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function afterSave(
        \Magento\Sales\Model\Order\Invoice $subject,
        \Magento\Sales\Model\Order\Invoice $result
    ) {
        // Queue the invoice to be sent to AvaTax
        if ($this->avaTaxConfig->isModuleEnabled() && $this->queueSubmitEnabled()) {

            $entityType = $this->eavConfig->getEntityType($result->getEntityType());

            /** @var \ClassyLlama\AvaTax\Model\Queue $queue */
            $queue = $this->queueFactory->create();

            $queue->setData('store_id', $result->getStoreId());
            $queue->setData('entity_type_id', $entityType->getEntityTypeId());
            $queue->setData('entity_type_code', $result->getEntityType());
            $queue->setData('entity_id', $result->getEntityId());
            $queue->setData('increment_id', $result->getIncrementId());
            $queue->setData('queue_status', 'pending');
            $queue->setData('attempts', 0);
            $queue->save();
        }

        return $result;
    }
}