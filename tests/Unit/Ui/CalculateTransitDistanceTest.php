<?php

namespace LegacyFighter\Cabs\Tests\Unit\Ui;

use LegacyFighter\Cabs\Distance\Distance;
use LegacyFighter\Cabs\DTO\TransitDTO;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Driver;
use LegacyFighter\Cabs\Entity\DriverLicense;
use LegacyFighter\Cabs\Entity\Transit;
use LegacyFighter\Cabs\Tests\Common\PrivateProperty;
use PHPUnit\Framework\TestCase;

class CalculateTransitDistanceTest extends TestCase
{
    /**
     * @test
     */
    public function shouldNotWorkWithInvalidUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->transitForDistance(2.0)->getDistance('invalid');
    }

    /**
     * @test
     */
    public function shouldRepresentAsKm(): void
    {
        self::assertEquals('10km', $this->transitForDistance(10)->getDistance('km'));
        self::assertEquals('10.123km', $this->transitForDistance(10.123)->getDistance('km'));
        self::assertEquals('10.123km', $this->transitForDistance(10.12345)->getDistance('km'));
        self::assertEquals('0km', $this->transitForDistance(0)->getDistance('km'));
    }

    /**
     * @test
     */
    public function shouldRepresentAsMeters(): void
    {
        self::assertEquals('10000m', $this->transitForDistance(10)->getDistance('m'));
        self::assertEquals('10123m', $this->transitForDistance(10.123)->getDistance('m'));
        self::assertEquals('10123m', $this->transitForDistance(10.12345)->getDistance('m'));
        self::assertEquals('0m', $this->transitForDistance(0)->getDistance('m'));
    }

    /**
     * @test
     */
    public function shouldRepresentAsMiles(): void
    {
        self::assertEquals('6.214miles', $this->transitForDistance(10)->getDistance('miles'));
        self::assertEquals('6.290miles', $this->transitForDistance(10.123)->getDistance('miles'));
        self::assertEquals('6.290miles', $this->transitForDistance(10.12345)->getDistance('miles'));
        self::assertEquals('0miles', $this->transitForDistance(0)->getDistance('miles'));
    }

    private function transitForDistance(float $km): TransitDTO
    {
        $address = new Address('country', 'city', 'street', 1);
        $address->setName('name');
        $address->setPostalCode('111');
        $address->setDistrict('district');
        PrivateProperty::setId(1, $address);

        $client = new Client();
        $client->setName('Janusz');
        $client->setLastName('Kowalski');
        $client->setType(Client::TYPE_NORMAL);
        $client->setDefaultPaymentType(Client::PAYMENT_TYPE_MONTHLY_INVOICE);
        PrivateProperty::setId(1, $client);

        $transit = new Transit($client, $address, $address, CarType::CAR_CLASS_VAN, new \DateTimeImmutable(), Distance::ofKm($km));
        PrivateProperty::setId(1, $transit);

        $driver = new Driver();
        $driver->setFirstName('Jan');
        $driver->setLastName('Kowalski');
        $driver->setDriverLicense(DriverLicense::withLicense('FARME100165AB5EW'));
        $driver->setType(Driver::TYPE_REGULAR);
        $driver->setStatus(Driver::STATUS_ACTIVE);
        PrivateProperty::setId(1, $driver);

        $transit->proposeDriver($driver);
        $transit->accept($driver, new \DateTimeImmutable());

        $transit->complete($transit->getTo(), $transit->getKm(), new \DateTimeImmutable());

        return TransitDTO::from($transit);
    }
}
