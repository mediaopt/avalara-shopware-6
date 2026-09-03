<?php
declare(strict_types=1);

namespace MoptAvalara6;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use MoptAvalara6\Bootstrap\Form;

class MoptAvalara6 extends Plugin
{
    const PLUGIN_NAME = 'MoptAvalara6';
    const PLUGIN_VERSION = '4.1.0';
    const AVALARA_APP_VERSION = 'a0n5a00000hvQLaAAM';

    /**
     * @param InstallContext $installContext
     * @return void
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->addCustomFields($installContext);
    }

    /**
     * @param UninstallContext $uninstallContext
     * @return void
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);
            return;
        }

        $this->removeCustomField($uninstallContext);
        parent::uninstall($uninstallContext);
    }

    /**
     * @param ActivateContext $activateContext
     * @return void
     */
    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
    }

    /**
     * @param InstallContext $installContext
     * @return void
     */
    private function addCustomFields(InstallContext $installContext)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        foreach (Form::CUSTOM_FIELDSET_LIST as $fieldSet => $method) {

            if (!$this->customFieldsExist($installContext->getContext(), $fieldSet)) {
                $customFieldSetRepository->upsert([$this->$method()], $installContext->getContext());
            }
        }
    }

    /**
     * @param UninstallContext $uninstallContext
     * @return void
     */
    private function removeCustomField(UninstallContext $uninstallContext)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        foreach (Form::CUSTOM_FIELDSET_LIST as $fieldSet => $method) {
            if ($fieldId = $this->customFieldsExist($uninstallContext->getContext(), $fieldSet)) {
                $customFieldSetRepository->delete([['id' => $fieldId]], $uninstallContext->getContext());
            }
        }
    }

    /**
     * @param Context $context
     * @param string $fieldsetName
     * @return null|string
     */
    private function customFieldsExist(Context $context, string $fieldsetName)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', [$fieldsetName]));

        $ids = $customFieldSetRepository->searchIds($criteria, $context);

        return current($ids->getIds());
    }

    /**
     * @return array
     */
    private function getShippingTaxCodeFieldset(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => Form::CUSTOM_FIELD_AVALARA_SHIPPING_TAX_CODE_FIELDSET,
            'config' => [
                'label' => [
                    'de-DE' => 'Versand Tax Code',
                    'en-GB' => 'Shipment Tax Code'
                ]
            ],
            'customFields' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => Form::CUSTOM_FIELD_AVALARA_SHIPPING_TAX_CODE,
                    'type' => CustomFieldTypes::TEXT,
                    'allow_cart_expose' => true,
                ]
            ],
            'relations' => [
                [
                    'id' => Uuid::randomHex(),
                    'entityName' => 'shipping_method'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function getProductTaxCodeFieldset(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE_FIELDSET,
            'config' => [
                'label' => [
                    'de-DE' => 'Produkt Tax Code',
                    'en-GB' => 'Product Tax Code'
                ]
            ],
            'customFields' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE,
                    'type' => CustomFieldTypes::TEXT,
                    'allow_cart_expose' => true,
                ]
            ],
            'relations' => [
                [
                    'id' => Uuid::randomHex(),
                    'entityName' => 'product'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function getCategoryTaxCodeFieldset(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => Form::CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE_FIELDSET,
            'config' => [
                'label' => [
                    'de-DE' => 'Kategorie Tax Code',
                    'en-GB' => 'Category Tax Code'
                ]
            ],
            'customFields' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => Form::CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE,
                    'type' => CustomFieldTypes::TEXT,
                    'allow_cart_expose' => true,
                ]
            ],
            'relations' => [
                [
                    'id' => Uuid::randomHex(),
                    'entityName' => 'category'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function getCustomerCodeFieldset(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => Form::CUSTOM_FIELD_AVALARA_CUSTOMER_CODE_FIELDSET,
            'config' => [
                'label' => [
                    'de-DE' => 'Avalara Kundencode',
                    'en-GB' => 'Avalara Customer Code'
                ]
            ],
            'customFields' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => Form::CUSTOM_FIELD_AVALARA_CUSTOMER_CODE,
                    'type' => CustomFieldTypes::TEXT,
                ]
            ],
            'relations' => [
                [
                    'id' => Uuid::randomHex(),
                    'entityName' => 'customer'
                ]
            ]
        ];
    }
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
