<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests;

use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\Client;
use Symfony\Contracts\HttpClient\Test\HttpClientTestCase;

trait WithFixtures
{
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
