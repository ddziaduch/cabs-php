<?php

namespace LegacyFighter\Cabs\Tests\Unit\Entity;

use LegacyFighter\Cabs\Distance\Distance;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Driver;
use LegacyFighter\Cabs\Entity\Transit;
use LegacyFighter\Cabs\Money\Money;
use LegacyFighter\Cabs\Tests\Common\PrivateProperty;
use PHPUnit\Framework\TestCase;

class CalculateTransitPriceTest extends TestCase
{
    /**
     * @test
     */
    public function cannotCalculatePriceWhenTransitIsCancelled(): void
    {
        //given
        $transit = $this->transit(20);
        $transit->cancel();

        //expect
        $this->expectException(\RuntimeException::class);
        $transit->calculateFinalCosts();
    }

    /**
     * @test
     */
    public function cannotEstimatePriceWhenTransitIsCompleted(): void
    {
        //given
        $transit = $this->transit(20);
        $this->completeTransit($transit);

        //expect
        $this->expectException(\RuntimeException::class);
        $transit->estimateCost();
    }

    /**
     * @test
     */
    public function calculatePriceOnRegularDay(): void
    {
        //given
        $transit = $this->transit(20, $this->friday());
        $this->completeTransit($transit);

        //when
        $price = $transit->calculateFinalCosts();

        //then
        self::assertEquals(Money::from(2900), $price); //29.00
    }

    /**
     * @test
     */
    public function estimatePriceOnRegularDay(): void
    {
        //given
        $transit = $this->transit(20, $this->friday());

        //when
        $price = $transit->estimateCost();

        //then
        self::assertEquals(Money::from(2900), $price); //29.00
    }

    /**
     * @test
     */
    public function calculatePriceOnSunday(): void
    {
        //given
        $transit = $this->transit(20, $this->sunday());
        $this->completeTransit($transit);

        //when
        $price = $transit->calculateFinalCosts();

        //then
        self::assertEquals(Money::from(3800), $price); //39.00
    }

    /**
     * @test
     */
    public function calculatePriceOnNewYearsEve(): void
    {
        //given
        $transit = $this->transit(20, $this->newYearsEve());
        $this->completeTransit($transit);

        //when
        $price = $transit->calculateFinalCosts();

        //then
        self::assertEquals(Money::from(8100), $price); //81.00
    }

    /**
     * @test
     */
    public function calculatePriceOnSaturday(): void
    {
        //given
        $transit = $this->transit(20, $this->saturday());
        $this->completeTransit($transit);

        //when
        $price = $transit->calculateFinalCosts();

        //then
        self::assertEquals(Money::from(3800), $price); //38.00
    }

    /**
     * @test
     */
    public function calculatePriceOnSaturdayNight(): void
    {
        //given
        $transit = $this->transit(20, $this->saturdayNight());
        $this->completeTransit($transit);

        //when
        $price = $transit->calculateFinalCosts();

        //then
        self::assertEquals(Money::from(6000), $price); //60.00
    }

    private function transit(
        float $km,
        ?\DateTimeImmutable $dateTime = null
    ): Transit {
        $transit = new Transit(
            new Client(),
            new Address('Polska', 'Warszawa', 'Młynarska', 20),
            new Address('Polska', 'Warszawa', 'Zytnia', 20),
            CarType::CAR_CLASS_VAN,
            $dateTime ?? new \DateTimeImmutable(),
            Distance::ofKm($km),
        );
        PrivateProperty::setId(1, $transit);

        $driver = new Driver();
        PrivateProperty::setId(1, $driver);

        $transit->proposeDriver($driver);
        $transit->accept($driver, new \DateTimeImmutable());

        return $transit;
    }

    private function friday(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2021-04-16 08:30');
    }

    private function newYearsEve(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2021-12-31 08:30');
    }

    private function saturday(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2021-04-17 08:30');
    }

    private function sunday(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2021-04-18 08:30');
    }

    private function saturdayNight(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2021-04-17 19:30');
    }

    private function completeTransit(Transit $transit): void
    {
        $transit->complete($transit->getTo(), $transit->getKm(), new \DateTimeImmutable());
    }
}
