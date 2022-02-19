<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests;

use LegacyFighter\Cabs\Common\Clock;
use LegacyFighter\Cabs\Entity\Address;
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
        ?int $id = 1,
        ?\DateTimeImmutable $dateTime = null,
        ?Driver $driver = null,
        ?int $price = null
    ): Transit {
        $transit = $id === null
            ? new Transit()
            : new class() extends Transit {
                public function __construct()
                {
                    parent::__construct();
                    $this->id = 1;
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

        return $transit;
    }

    private function getClient(): Client
    {
        $client = new Client();
        $client->setType(Client::TYPE_NORMAL);
        $client->setName('Jan');
        $client->setLastName('Kowalski');
        $client->setDefaultPaymentType(Client::PAYMENT_TYPE_POST_PAID);

        return $client;
    }

    private function getAddress(): Address
    {
        $address = new Address('Poland', 'Gdańsk', 'Nowe Ogrody', 1);
        $address->setPostalCode('80-100');
        $address->setName('Test');
        $address->setDistrict('Śródmieście');

        return $address;
    }
}
