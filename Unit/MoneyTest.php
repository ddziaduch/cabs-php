<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Unit;

use LegacyFighter\Cabs\VO\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreatedFromInt()
    {
        self::assertSame(100, Money::from(100)->toInt());
        self::assertSame(666, Money::from(666)->toInt());
        self::assertSame(94939393, Money::from(94939393)->toInt());
    }

    /**
     * @test
     */
    public function zeroIsPredefined()
    {
        self::assertSame(0, Money::zero()->toInt());
    }

    /**
     * @test
     */
    public function supportsAddition()
    {
        self::assertSame(25, Money::from(20)->add(Money::from(5))->toInt());
        self::assertSame(46, Money::from(22)->add(Money::from(24))->toInt());
    }

    public function supportsSubtraction()
    {
        self::assertSame(15, Money::from(20)->sub(Money::from(5))->toInt());
        self::assertSame(2, Money::from(24)->sub(Money::from(22))->toInt());
    }

    /**
     * @test
     */
    public function supportsPercentage()
    {
        self::assertSame(10, Money::from(100)->percentage(10)->toInt());
        self::assertSame(4, Money::from(50)->percentage(2)->toInt());
    }

    /**
     * @test
     */
    public function canBePresentedAsString()
    {
        self::assertSame('10.00', (string) Money::from(10));
        self::assertSame('123.00', (string) Money::from(123));
    }
}
