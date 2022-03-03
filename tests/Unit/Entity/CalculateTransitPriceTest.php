<?php

namespace LegacyFighter\Cabs\Tests\Unit\Entity;

use LegacyFighter\Cabs\Distance\Distance;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
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
        $transit = $this->transit(Transit::STATUS_CANCELLED, 20);

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
        $transit = $this->transit(Transit::STATUS_COMPLETED, 20);

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
        $transit = $this->transit(Transit::STATUS_COMPLETED, 20);

        //friday
        $this->transitWasOnDoneOnFriday($transit);
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
        $transit = $this->transit(Transit::STATUS_DRAFT, 20);

        //friday
        $this->transitWasOnDoneOnFriday($transit);
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
        $transit = $this->transit(Transit::STATUS_COMPLETED, 20);
        //and
        $this->transitWasDoneOnSunday($transit);

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
        $transit = $this->transit(Transit::STATUS_COMPLETED, 20);
        //and
        $this->transitWasDoneOnNewYearsEve($transit);

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
        $transit = $this->transit(Transit::STATUS_COMPLETED, 20);
        //and
        $this->transitWasDoneOnSaturday($transit);

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
        $transit = $this->transit(Transit::STATUS_COMPLETED, 20);
        //and
        $this->transitWasDoneOnSaturdayNight($transit);

        //when
        $price = $transit->calculateFinalCosts();

        //then
        self::assertEquals(Money::from(6000), $price); //60.00
    }

    private function transit(string $status, float $km): Transit
    {
        $transit = new Transit(
            new Client(),
            new Address('Polska', 'Warszawa', 'Młynarska', 20),
            new Address('Polska', 'Warszawa', 'Zytnia', 20),
            CarType::CAR_CLASS_VAN,
            new \DateTimeImmutable(),
            Distance::ofKm($km),
        );
        PrivateProperty::setId(1, $transit);
        $transit->setStatus($status);
        return $transit;
    }

    private function transitWasOnDoneOnFriday(Transit $transit): void
    {
        $transit->setDateTime(new \DateTimeImmutable('2021-04-16 08:30'));
    }

    private function transitWasDoneOnNewYearsEve(Transit $transit): void
    {
        $transit->setDateTime(new \DateTimeImmutable('2021-12-31 08:30'));
    }

    private function transitWasDoneOnSaturday(Transit $transit): void
    {
        $transit->setDateTime(new \DateTimeImmutable('2021-04-17 08:30'));
    }

    private function transitWasDoneOnSunday(Transit $transit): void
    {
        $transit->setDateTime(new \DateTimeImmutable('2021-04-18 08:30'));
    }

    private function transitWasDoneOnSaturdayNight(Transit $transit): void
    {
        $transit->setDateTime(new \DateTimeImmutable('2021-04-17 19:30'));
    }
}
