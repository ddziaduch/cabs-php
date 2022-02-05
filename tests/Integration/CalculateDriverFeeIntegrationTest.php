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
use LegacyFighter\Cabs\VO\Money;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CalculateDriverFeeIntegrationTest extends KernelTestCase
{
    /**
     * @test
     */
    public function itCalculatesFlatDriverFee(): void
    {
        $driver = $this->createDriver();
        $transit = $this->createTransit($driver);

        $this->createDriverFee($driver, DriverFee::TYPE_FLAT, 10);

        $fee = self::getContainer()->get(DriverFeeService::class)->calculateDriverFee($transit->getId());

        self::assertSame(90, $fee);
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

        self::assertSame(20, $fee);
    }

    /**
     * @test
     */
    public function itUsesMinimumFee(): void
    {
        $driver = $this->createDriver();
        $transit = $this->createTransit($driver);

        $this->createDriverFee($driver, DriverFee::TYPE_PERCENTAGE, 5, 10);

        $fee = self::getContainer()->get(DriverFeeService::class)->calculateDriverFee($transit->getId());

        self::assertSame(10, $fee);
    }

    private function createDriver(): Driver
    {
        $driverService = self::getContainer()->get(DriverService::class);

        return $driverService->createDriver('FARME100165AB5EW', 'John', 'Smith', 'regular', 'active', null);
    }

    private function createTransit(Driver $driver): Transit
    {
        $transit = new Transit();
        $transit->setStatus('draft');
        $transit->setPrice(Money::from(100));
        $transit->setDriver($driver);
        $transit->setDateTime(new \DateTimeImmutable());

        self::getContainer()->get(TransitRepository::class)->save($transit);

        return $transit;
    }

    private function createDriverFee(Driver $driver, string $type, int $amount, ?int $min = null): void
    {
        $driverFee = new DriverFee($type, $driver, $amount, $min);
        self::getContainer()->get(DriverFeeRepository::class)->save($driverFee);
    }
}
