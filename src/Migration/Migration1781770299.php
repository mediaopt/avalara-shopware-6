<?php declare(strict_types=1);

namespace MoptAvalara6\Migration;

use MoptAvalara6\Bootstrap\Form;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1781770299 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1781770299;
    }

    // Compatibility for old clients
    public function update(Connection $connection): void
    {
        $fieldNames = [
            Form::CUSTOM_FIELD_AVALARA_SHIPPING_TAX_CODE,
            Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE,
            Form::CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE,
        ];

        $quotedNames = array_map(fn(string $name) => $connection->quote($name), $fieldNames);

        $qb = $connection->createQueryBuilder();
        $qb->update('custom_field')
            ->set('allow_cart_expose', '1')
            ->where($qb->expr()->in('name', $quotedNames));
        $qb->executeQuery();
    }
}

