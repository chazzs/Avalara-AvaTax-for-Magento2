<?php
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

namespace ClassyLlama\AvaTax\Ui\Component;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\AbstractComponent;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use ClassyLlama\AvaTax\Model\Certificates as CertificatesList;
use ClassyLlama\AvaTax\Model\ResourceModel\Config as ResourceModelConfig;
use ClassyLlama\AvaTax\Framework\Interaction\Rest\Company as RestCompany;
use Magento\Backend\Model\Url as ModelUrl;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use ClassyLlama\AvaTax\Helper\DocumentManagementConfig;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\User\Model\User;
use Magento\Framework\DataObject;

/**
 * Class TaxCertificates
 * @package ClassyLlama\AvaTax\Ui\Component
 */
class TaxCertificates extends AbstractComponent implements TabInterface
{
    /**
     * Component name
     *
     * @var string
     */
    const NAME = 'taxCertificates';

    /**
     * @var string
     */
    const COMPONENT = 'ClassyLlama_AvaTax/js/form/certificates-fieldset';

    /**
     * @var ResourceModelConfig
     */
    protected $configResourceModel;

    /**
     * @var RestCompany
     */
    protected $companyRest;

    /**
     * @var ModelUrl
     */
    protected $backendUrl;

    /**
     * @var Context
     */
    protected $sessionContext;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var DocumentManagementConfig
     */
    protected $documentManagementConfig;

    /**
     * @var CertificatesList
     */
    private $certificatesList;

    /**
     * @var AuthSession
     */
    private $authSession;

    /**
     * TaxCertificates constructor.
     * @param AuthSession $authSession
     * @param CertificatesList $certificatesList
     * @param ContextInterface $context
     * @param ResourceModelConfig $configResourceModel
     * @param RestCompany $companyRest
     * @param ModelUrl $backendUrl
     * @param Context $sessionContext
     * @param Registry $registry
     * @param CustomerRepositoryInterface $customerRepository
     * @param DocumentManagementConfig $documentManagementConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        AuthSession $authSession,
        CertificatesList $certificatesList,
        ContextInterface $context,
        ResourceModelConfig $configResourceModel,
        RestCompany $companyRest,
        ModelUrl $backendUrl,
        Context $sessionContext,
        Registry $registry,
        CustomerRepositoryInterface $customerRepository,
        DocumentManagementConfig $documentManagementConfig,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->configResourceModel = $configResourceModel;
        $this->companyRest = $companyRest;
        $this->backendUrl = $backendUrl;
        $this->sessionContext = $sessionContext;
        $this->registry = $registry;
        $this->customerRepository = $customerRepository;
        $this->documentManagementConfig = $documentManagementConfig;
        $this->certificatesList = $certificatesList;
        $this->authSession = $authSession;
    }

    /**
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        return static::NAME;
    }

    /**
     * @return bool
     */
    public function shouldShowWarning()
    {
        try {
            return $this->configResourceModel->getConfigCount(
                    [
                        \ClassyLlama\AvaTax\Helper\Config::XML_PATH_AVATAX_DEVELOPMENT_COMPANY_CODE,
                        \ClassyLlama\AvaTax\Helper\Config::XML_PATH_AVATAX_PRODUCTION_COMPANY_CODE
                    ]
                ) > 2;
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * @return array
     * @throws \ClassyLlama\AvaTax\Exception\AvataxConnectionException
     */
    protected function getAvailableExemptionZones()
    {
        $zones = $this->companyRest->getCertificateExposureZones();

        return array_map(
            function ($zone) {
                return $zone->name;
            },
            $zones->value
        );
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws \ClassyLlama\AvaTax\Exception\AvataxConnectionException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepare()
    {
        if (!$this->canShowTab()) {
            parent::prepare();

            return;
        }

        $gridComponent = $this->getComponent('customer_tax_certificates_grid');
        $config = $gridComponent->getData('config');
        $config['shouldShowWarning'] = $this->shouldShowWarning();

        /** @var User|null $user */
        $user = $this->authSession->getUser();
        if (null !== $user && !empty($userId = (int)$user->getId())) {
            /** @var int $userId */
            $userId = !empty((int)$customerId = $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID)) ? $customerId : $userId;
            /** @var array $certificates */
            $certificates = (array)$this->certificatesList->getCertificatesList($userId);
            $data = [];
            /** @var DataObject $certificate */
            foreach ($certificates as $certificate) {
                if ($certificate instanceof DataObject) {
                    /** @var array $item */
                    $item = $certificate->getData();
                    $item['exemption_reason'] = $certificate->getData('exemption_reason')->getData();
                    $item['exposure_zone'] = $certificate->getData('exposure_zone')->getData();
                    $item['validated_exemption_reason'] = $certificate->getData('validated_exemption_reason')->getData();
                    $data[] = $item;
                }
            }
            $config['certificates'] = $data;
        }

        $gridComponent->setData('config', $config);

        // Configure our customer component instead of the fieldset
        $jsConfig = $this->getData('js_config');
        $jsConfig['component'] = static::COMPONENT;
        $this->setData('js_config', $jsConfig);

        // Configure the component for adding certificates
        $config = $this->getData('config');
        $config['customer_id'] = $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        $customer = $this->customerRepository->getById($config['customer_id']);
        $config['has_default_billing_address'] = (bool)$customer->getDefaultBilling();
        $config['invite_url'] = $this->backendUrl->getUrl(
            'avatax/invite',
            ['form_key' => $this->sessionContext->getFormKey(), 'customer_id' => $config['customer_id']]
        );
        $config['token_url'] = $this->backendUrl->getUrl(
            'avatax/certificatestoken/get',
            ['form_key' => $this->sessionContext->getFormKey()]
        );
        $config['available_exemption_zones'] = $this->getAvailableExemptionZones();
        $this->setData('config', $config);

        parent::prepare();
    }

    /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return '';
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return '';
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        false;
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        $customerId = $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);

        try {
            return $customerId !== null && $this->documentManagementConfig->isEnabled(
                    $this->customerRepository->getById($customerId)->getStoreId()
                );
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        false;
    }
}
