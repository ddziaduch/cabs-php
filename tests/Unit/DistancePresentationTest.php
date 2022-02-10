<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Unit;

use LegacyFighter\Cabs\DTO\TransitDTO;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Transit;
use PHPUnit\Framework\TestCase;

class DistancePresentationTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideDataForCanBePresentedAs
     */
    public function canBePresentedAs(string $unit, string $expectedDistance): void
    {
        $transitDTO = $this->getTransitDTO();
        $actualDistance = $transitDTO->getDistance($unit);

        self::assertSame($expectedDistance, $actualDistance);
    }

    public function provideDataForCanBePresentedAs(): iterable
    {
        yield 'kilometers' => [
            'unit' => 'km',
            'expectedDistance' => '100km',
        ];

        yield 'miles' => [
            'unit' => 'miles',
            'expectedDistance' => '62.137miles',
        ];

        yield 'meters' => [
            'unit' => 'm',
            'expectedDistance' => '100000m',
        ];
    }

    /**
     * @test
     */
    public function cannotBePresentedWhenUnitIsUnsupported(): void
    {
        $transitDTO = $this->getTransitDTO();
        $this->expectException(\InvalidArgumentException::class);
        $transitDTO->getDistance('inches');
    }

    private function getTransitDTO(): TransitDTO
    {
        $transit = new class() extends Transit {
            public function __construct()
            {
                parent::__construct();
                $this->id = 1;
            }
        };

        $addressTo = new Address('Poland', 'Gdansk', 'Nowe Ogrody', 1);
        $addressTo->setPostalCode('80-100');
        $addressTo->setName('address to');

        $addressFrom = new Address('Poland', 'Gdansk', 'Nowe Ogrody', 100);
        $addressFrom->setPostalCode('80-100');
        $addressFrom->setName('address from');

        $client = new class() extends Client {
            public function __construct()
            {
                parent::__construct();
                $this->id = 1;
            }
        };
        $client->setType(Client::TYPE_NORMAL);
        $client->setName('Jan');
        $client->setLastName('Kowalski');
        $client->setDefaultPaymentType(Client::PAYMENT_TYPE_POST_PAID);

        $transit->setClient($client);
        $transit->setTo($addressTo);
        $transit->setFrom($addressFrom);
        $transit->setStatus(Transit::STATUS_DRAFT);
        $transit->setDateTime(new \DateTimeImmutable());
        $transit->setKm(100);

        return TransitDTO::from($transit);
    }
}
