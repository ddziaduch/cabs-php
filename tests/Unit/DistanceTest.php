<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Unit;

use LegacyFighter\Cabs\VO\Distance;
use PHPUnit\Framework\TestCase;

class DistanceTest extends TestCase
{
    /**
     * @test
     */
    public function cannotBeFormattedWhenUnitIsNotSupported(): void
    {
        $distance = Distance::ofKm(100);
        $this->expectException(\InvalidArgumentException::class);
        $distance->formatAs('ultra meters');
    }

    /**
     * @test
     * @dataProvider provideDataForCanBeFormattedAs
     */
    public function canBeFormattedAs(string $unit, string $expectedFormat): void
    {
        $distance = Distance::ofKm(100);
        self::assertSame($expectedFormat, $distance->formatAs($unit));
    }

    public function provideDataForCanBeFormattedAs(): iterable
    {
        yield 'kilometers' => [
            'unit' => 'km',
            'expectedFormat' => '100km',
        ];

        yield 'miles' => [
            'unit' => 'miles',
            'expectedFormat' => '62.137miles',
        ];

        yield 'meters' => [
            'unit' => 'm',
            'expectedFormat' => '100000m',
        ];
    }
}
