<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests;

use LegacyFighter\Cabs\Common\Clock;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Driver;
use LegacyFighter\Cabs\Entity\Transit;
use LegacyFighter\Cabs\VO\Distance;
use LegacyFighter\Cabs\VO\Money;

trait WithFixtures
{
    private function getClock(): Clock
    {
        return new class() implements Clock {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2022-01-01T00:00:00Z');
            }
        };
    }

    public function getTransit(
        ?int $id = null,
        ?\DateTimeImmutable $dateTime = null,
        ?Driver $driver = null,
        ?int $price = null
    ): Transit {
        $transit = $id === null
            ? new Transit()
            : new class($id) extends Transit {
                public function __construct(int $id)
                {
                    parent::__construct();
                    $this->id = $id;
                }
            };
        $transit->setStatus(Transit::STATUS_DRAFT);
        $transit->setDateTime($dateTime ?? $this->getClock()->now());
        $transit->setKm(Distance::ofKm(10.0));
        if ($driver !== null) {
            $transit->setDriver($driver);
        }
        if ($price !== null) {
            $transit->setPrice(Money::from($price));
        }
        $transit->setCarType(CarType::CAR_CLASS_ECO);

        return $transit;
    }

    private function getClient(?int $id = null): Client
    {
        $client = $id === null
            ? new Client()
            : new class($id) extends Client {
                public function __construct(int $id)
                {
                    parent::__construct();
                    $this->id = $id;
                }
            };
        $client->setType(Client::TYPE_NORMAL);
        $client->setName('Jan');
        $client->setLastName('Kowalski');
        $client->setDefaultPaymentType(Client::PAYMENT_TYPE_POST_PAID);

        return $client;
    }

    private function getAddress(
        ?string $city = null,
        ?string $street = null,
        ?int $buildingNumber = null,
        ?string $postCode = null,
        ?string $district = null,
        ?string $name = null
    ): Address {
        $address = new Address('Poland', $city ?? 'Gdańsk', $street ?? 'Nowe Ogrody', $buildingNumber ?? 1);
        $address->setPostalCode($postCode ?? '80-100');
        $address->setName($name ?? 'Test');
        $address->setDistrict($district ?? 'Śródmieście');

        return $address;
    }
}
