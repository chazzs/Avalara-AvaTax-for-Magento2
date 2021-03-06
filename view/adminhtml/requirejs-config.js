/**
 * ClassyLlama_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2018 Avalara, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

var config = {
    map: {
        '*': {
            companyCode: 'ClassyLlama_AvaTax/js/form/field/company-code'
        }
    },
    config: {
        mixins: {
            'ClassyLlama_AvaTax/js/action/account-add-exemption': {
                'ClassyLlama_AvaTax/js/admin-account-add-exemption': true
            }
        }
    }
};
