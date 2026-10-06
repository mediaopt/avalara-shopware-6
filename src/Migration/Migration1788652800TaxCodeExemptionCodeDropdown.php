<?php declare(strict_types=1);

namespace MoptAvalara6\Migration;

use MoptAvalara6\Bootstrap\Form;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1788652800TaxCodeExemptionCodeDropdown extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1788652800;
    }

    public function update(Connection $connection): void
    {
        $taxCodeFields = [
            Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE,
            Form::CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE,
        ];

        foreach ($taxCodeFields as $fieldName) {
            $connection->executeStatement(
                "UPDATE custom_field
                 SET config = JSON_SET(COALESCE(config, '{}'),
                     '$.componentName', 'avalara-tax-code-select',
                     '$.customFieldType', 'text'
                 )
                 WHERE name = :name",
                ['name' => $fieldName]
            );
        }

        $connection->executeStatement(
            "UPDATE custom_field
             SET config = JSON_SET(COALESCE(config, '{}'),
                 '$.componentName', 'avalara-exemption-code-select',
                 '$.customFieldType', 'text'
             )
             WHERE name = :name",
            ['name' => Form::CUSTOM_FIELD_AVALARA_CUSTOMER_CODE]
        );
    }
}
