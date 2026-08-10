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

    const PLUGIN_VERSION = '3.0.10';

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
        $fieldIds = $this->customFieldsExist($installContext->getContext());

        if ($fieldIds) {
            return;
        }

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetRepository->upsert([
            $this->getShippingTaxCodeFieldset(),
            $this->getProductTaxCodeFieldset(),
            $this->getCategoryTaxCodeFieldset(),
        ], $installContext->getContext());
    }

    /**
     * @param UninstallContext $uninstallContext
     * @return void
     */
    private function removeCustomField(UninstallContext $uninstallContext)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $fieldIds = $this->customFieldsExist($uninstallContext->getContext());

        if ($fieldIds) {
            $customFieldSetRepository->delete(array_values($fieldIds->getData()), $uninstallContext->getContext());
        }
    }

    /**
     * @param Context $context
     * @return mixed
     */
    private function customFieldsExist(Context $context)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter(
            'name',
            [
                Form::CUSTOM_FIELD_AVALARA_SHIPPING_TAX_CODE_FIELDSET,
                Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE_FIELDSET,
                Form::CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE_FIELDSET,
            ]
        ));

        $ids = $customFieldSetRepository->searchIds($criteria, $context);

        return $ids->getTotal() > 0 ? $ids : null;
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
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
