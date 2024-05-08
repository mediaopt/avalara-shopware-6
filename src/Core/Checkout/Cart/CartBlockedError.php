<?php declare(strict_types=1);

namespace MoptAvalara6\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Error\Error;

class CartBlockedError extends Error
{
    private const KEY = 'moptavalara6.tax-calculation-error';

    public function getId(): string
    {
        return '';
    }

    public function getMessageKey(): string
    {
        return self::KEY;
    }

    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }

    public function getParameters(): array
    {
        return [];
    }
}
