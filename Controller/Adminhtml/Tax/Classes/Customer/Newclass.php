<?php

namespace ClassyLlama\AvaTax\Controller\Adminhtml\Tax\Classes\Customer;

use ClassyLlama\AvaTax\Controller\Adminhtml\Tax\Classes\Base;

/**
 * Adminhtml controller
 */
class Newclass extends Base\Newclass
{
    /**
     * Tax class type
     *
     * @var null|string
     */
    protected $classType = \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_CUSTOMER;
}
