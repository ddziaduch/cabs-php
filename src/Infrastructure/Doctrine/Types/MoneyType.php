<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Infrastructure\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;
use LegacyFighter\Cabs\VO\Money;

class MoneyType extends IntegerType
{
    public function getName(): string
    {
        return 'money';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value === null ? null : Money::from((int) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }
        assert($value instanceof Money);
        return $value->toInt();
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
