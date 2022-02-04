<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Unit;

use LegacyFighter\Cabs\Entity\Transit;
use PHPUnit\Framework\TestCase;

class CalculateTransitPriceTest extends TestCase
{
    /**
     * @test
     * @dataProvider nonCompleteStatusDataProvider
     */
    public function cannotCalculateFinalCostsWhenStatusIsNotCompleted(string $status): void
    {
        $transit = $this->createTransit();
        $transit->setStatus($status);

        $this->expectException(\RuntimeException::class);

        $transit->calculateFinalCosts();
    }

    public function nonCompleteStatusDataProvider(): array
    {
        return [
            ['status' => 'draft'],
            ['status' => 'cancelled'],
            ['status' => 'waiting-for-driver-assigment'],
            ['status' => 'driver-assigment-failed'],
            ['status' => 'transit-to-passenger'],
            ['status' => 'in-transit'],
        ];
    }

    /**
     * @test
     */
    public function cannotEstimateFinalCostWhenStatusIsCompleted(): void
    {
        $transit = $this->createTransit();
        $transit->setStatus('completed');

        $this->expectException(\RuntimeException::class);

        $transit->estimateCost();
    }

    /**
     * @test
     */
    public function calculatePriceOnRegularDay(): void
    {
        $transit = $this->createTransit();
        $transit->setStatus('completed');
        $transit->setDateTime(new \DateTimeImmutable('2022-02-04T00:00:00Z'));
        $finalCosts = $transit->calculateFinalCosts();

        self::assertSame(1900, $finalCosts);
    }

    public function estimatePriceOnRegularDay(): void
    {
        $transit = $this->createTransit();
        $transit->setDateTime(new \DateTimeImmutable('2022-02-04T00:00:00Z'));
        $finalCosts = $transit->estimateCost();

        self::assertSame(1900, $finalCosts);
    }

    public function createTransit(): Transit
    {
        $transit = new class() extends Transit {
            public function __construct()
            {
                parent::__construct();
                $this->id = 1;
            }
        };
        $transit->setStatus('draft');
        $transit->setDateTime(new \DateTimeImmutable());
        $transit->setKm(10.0);

        return $transit;
    }
}
