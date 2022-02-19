<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Integration;

use LegacyFighter\Cabs\Entity\Driver;
use LegacyFighter\Cabs\Entity\DriverFee;
use LegacyFighter\Cabs\Entity\Transit;
use LegacyFighter\Cabs\Repository\DriverFeeRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;
use LegacyFighter\Cabs\Service\DriverService;
use LegacyFighter\Cabs\Tests\WithFixtures;
use LegacyFighter\Cabs\VO\Money;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CalculateDriverPeriodicPaymentsIntegrationTest extends KernelTestCase
{
    use WithFixtures;

    /**
     * @test
     */
    public function calculateMonthlyPayment()
    {
        $driver = $this->createDriver();

        $this->createTransit($driver, 10, new \DateTimeImmutable('2017-01-01T12:00:00Z'));
        $this->createTransit($driver, 20, new \DateTimeImmutable('2019-06-01T12:00:00Z'));
        $this->createTransit($driver, 30, new \DateTimeImmutable('2019-06-15T12:00:00Z'));
        $this->createTransit($driver, 40, new \DateTimeImmutable('2019-12-01T12:00:00Z'));
        $this->createTransit($driver, 50, new \DateTimeImmutable('2022-01-01T12:00:00Z'));

        $this->createDriverFee($driver, DriverFee::TYPE_FLAT, 10, Money::from(10));

        $paymentJune = self::getContainer()->get(DriverService::class)->calculateDriverMonthlyPayment(
            $driver->getId(),
            2019,
            6
        );

        $paymentDecember = self::getContainer()->get(DriverService::class)->calculateDriverMonthlyPayment(
            $driver->getId(),
            2019,
            12
        );

        self::assertSame(30, $paymentJune);
        self::assertSame(30, $paymentDecember);
    }

    /**
     * @test
     */
    public function calculateYearlyPayment()
    {
        $driver = $this->createDriver();

        $this->createTransit($driver, 10, new \DateTimeImmutable('2017-01-01T12:00:00Z'));
        $this->createTransit($driver, 20, new \DateTimeImmutable('2019-06-01T12:00:00Z'));
        $this->createTransit($driver, 30, new \DateTimeImmutable('2019-06-15T12:00:00Z'));
        $this->createTransit($driver, 40, new \DateTimeImmutable('2019-12-01T12:00:00Z'));
        $this->createTransit($driver, 50, new \DateTimeImmutable('2022-01-01T12:00:00Z'));

        $this->createDriverFee($driver, DriverFee::TYPE_FLAT, 10, Money::from(10));

        $payments = self::getContainer()->get(DriverService::class)->calculateDriverYearlyPayment(
            $driver->getId(),
            2019,
        );

        self::assertSame(0, $payments[1]);
        self::assertSame(0, $payments[2]);
        self::assertSame(0, $payments[3]);
        self::assertSame(0, $payments[4]);
        self::assertSame(0, $payments[5]);
        self::assertSame(30, $payments[6]);
        self::assertSame(0, $payments[7]);
        self::assertSame(0, $payments[8]);
        self::assertSame(0, $payments[9]);
        self::assertSame(0, $payments[10]);
        self::assertSame(0, $payments[11]);
        self::assertSame(30, $payments[12]);
    }

    private function createDriver(): Driver
    {
        $driverService = self::getContainer()->get(DriverService::class);

        return $driverService->createDriver('FARME100165AB5EW', 'John', 'Smith', 'regular', 'active', null);
    }

    private function createTransit(Driver $driver, int $price, \DateTimeImmutable $dateTime): Transit
    {
        $transit = $this->getTransit(
            null,
            $dateTime,
            $driver,
            $price,
        );

        self::getContainer()->get(TransitRepository::class)->save($transit);

        return $transit;
    }

    private function createDriverFee(Driver $driver, string $type, int $amount, ?Money $min = null): void
    {
        $driverFee = new DriverFee($type, $driver, $amount, $min);
        self::getContainer()->get(DriverFeeRepository::class)->save($driverFee);
    }
}
