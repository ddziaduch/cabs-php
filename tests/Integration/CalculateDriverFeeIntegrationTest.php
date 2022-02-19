<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Integration;

use LegacyFighter\Cabs\Entity\Driver;
use LegacyFighter\Cabs\Entity\DriverFee;
use LegacyFighter\Cabs\Entity\Transit;
use LegacyFighter\Cabs\Repository\DriverFeeRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;
use LegacyFighter\Cabs\Service\DriverFeeService;
use LegacyFighter\Cabs\Service\DriverService;
use LegacyFighter\Cabs\Tests\WithFixtures;
use LegacyFighter\Cabs\VO\Money;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CalculateDriverFeeIntegrationTest extends KernelTestCase
{
    use WithFixtures;

    /**
     * @test
     */
    public function itCalculatesFlatDriverFee(): void
    {
        $driver = $this->createDriver();
        $transit = $this->createTransit($driver);

        $this->createDriverFee($driver, DriverFee::TYPE_FLAT, 10);

        $fee = self::getContainer()->get(DriverFeeService::class)->calculateDriverFee($transit->getId());

        self::assertSame(90, $fee->toInt());
    }

    /**
     * @test
     */
    public function itCalculatesPercentageDriverFee(): void
    {
        $driver = $this->createDriver();
        $transit = $this->createTransit($driver);

        $this->createDriverFee($driver, DriverFee::TYPE_PERCENTAGE, 20);

        $fee = self::getContainer()->get(DriverFeeService::class)->calculateDriverFee($transit->getId());

        self::assertSame(20, $fee->toInt());
    }

    /**
     * @test
     */
    public function itUsesMinimumFee(): void
    {
        $driver = $this->createDriver();
        $transit = $this->createTransit($driver);

        $this->createDriverFee($driver, DriverFee::TYPE_PERCENTAGE, 5, Money::from(10));

        $fee = self::getContainer()->get(DriverFeeService::class)->calculateDriverFee($transit->getId());

        self::assertSame(10, $fee->toInt());
    }

    private function createDriver(): Driver
    {
        $driverService = self::getContainer()->get(DriverService::class);

        return $driverService->createDriver('FARME100165AB5EW', 'John', 'Smith', 'regular', 'active', null);
    }

    private function createTransit(Driver $driver): Transit
    {
        $transit = $this->getTransit(driver: $driver, price: 100);

        self::getContainer()->get(TransitRepository::class)->save($transit);

        return $transit;
    }

    private function createDriverFee(Driver $driver, string $type, int $amount, ?Money $min = null): void
    {
        $driverFee = new DriverFee($type, $driver, $amount, $min);
        self::getContainer()->get(DriverFeeRepository::class)->save($driverFee);
    }
}
