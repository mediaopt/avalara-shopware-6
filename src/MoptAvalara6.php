<?php
declare(strict_types=1);

namespace MoptAvalara6;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Kernel;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use MoptAvalara6\Bootstrap\Form;

class MoptAvalara6 extends Plugin
{
    const PLUGIN_NAME = 'MoptAvalara6';

    const PLUGIN_VERSION = '1.0';

    const ORDER_OPTIONS_LANG = 'Deutsch';

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
        $this->addOrderStatusSelector();
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
            $this->getOrderTaxDocumentCodeFieldset(),
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
                Form::CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE_FIELDSET,
            ]
        ));

        $ids = $customFieldSetRepository->searchIds($criteria, $context);

        return $ids->getTotal() > 0 ? $ids : null;
    }

    /**
     * @return array
     */
    private function getShippingTaxCodeFieldset()
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
    private function getOrderTaxDocumentCodeFieldset()
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => Form::CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE_FIELDSET,
            'config' => [
                'label' => [
                    'de-DE' => 'Steuerbelegcode bestellen',
                    'en-GB' => 'Order tax document code'
                ]
            ],
            'customFields' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => Form::CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE,
                    'type' => CustomFieldTypes::TEXT,
                ]
            ],
            'relations' => [
                [
                    'id' => Uuid::randomHex(),
                    'entityName' => 'order'
                ]
            ]
        ];
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function addOrderStatusSelector()
    {
        $configFile = __DIR__ . "/Resources/config/config.xml";
        $config = file_get_contents($configFile);
        $options = $this->buildOptionsNode();
        $config = str_replace("<!--options-->", $options, $config);
        file_put_contents($configFile, $config);
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function buildOptionsNode(): string
    {
        $connection = Kernel::getConnection();

        $lang = self::ORDER_OPTIONS_LANG; //todo take a lang from config
        $sql = "SELECT HEX(sms.id) AS id, smst.name AS name
            FROM state_machine sm
            LEFT JOIN state_machine_state sms ON sms.state_machine_id = sm.id
            LEFT JOIN state_machine_state_translation smst ON smst.state_machine_state_id = sms.id
            LEFT JOIN language lang ON lang.id = smst.language_id
            WHERE sm.technical_name = 'order.state'
            AND lang.name = '$lang';";

        $orderStates = $connection->executeQuery($sql)->fetchAllAssociative();

        $options = '<options>';
        foreach ($orderStates as $state) {
            $id = strtolower($state['id']);
            $options .= "<option><id>$id</id><name>{$state['name']}</name></option>";
        }
        $options .= '</options>';

        return $options;
    }
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
