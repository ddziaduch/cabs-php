<?php

namespace LegacyFighter\Cabs\Tests\Integration;

use LegacyFighter\Cabs\Common\Clock;
use LegacyFighter\Cabs\DTO\TransitDTO;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Transit;
use LegacyFighter\Cabs\Repository\AddressRepository;
use LegacyFighter\Cabs\Repository\ClientRepository;
use LegacyFighter\Cabs\Repository\DriverPositionRepository;
use LegacyFighter\Cabs\Repository\DriverRepository;
use LegacyFighter\Cabs\Repository\DriverSessionRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;
use LegacyFighter\Cabs\Service\AwardsService;
use LegacyFighter\Cabs\Service\CarTypeService;
use LegacyFighter\Cabs\Service\ClientService;
use LegacyFighter\Cabs\Service\DistanceCalculator;
use LegacyFighter\Cabs\Service\DriverFeeService;
use LegacyFighter\Cabs\Service\DriverNotificationService;
use LegacyFighter\Cabs\Service\GeocodingService;
use LegacyFighter\Cabs\Service\InvoiceGenerator;
use LegacyFighter\Cabs\Service\TransitService;
use LegacyFighter\Cabs\Tests\WithFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TransitLifeCycleIntegrationTest extends KernelTestCase
{
    use WithFixtures;

    /** @test */
    public function canBeCreated(): void
    {
        $clock = new class() implements Clock {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2022-01-01T00:00:00Z');
            }
        };

        $givenTransit = new class() extends Transit {
            public function __construct()
            {
                parent::__construct();
                $this->id = 1;
            }
        };
        $givenTransit->setFrom($this->getAddress());
        $givenTransit->setStatus(Transit::STATUS_DRAFT);
        $givenTransit->setTo($this->getAddress());
        $givenTransit->setDateTime($clock->now());

        $client = $this->getClient();
        $clientRepository = self::getContainer()->get(ClientRepository::class);
        $clientRepository->save($client);

        $givenTransit->setClient($client);
        $givenTransit->setCarType(CarType::CAR_CLASS_ECO);

        $dto = TransitDTO::from($givenTransit);
        $dto->getFrom()->setDistrict($givenTransit->getFrom()->getDistrict());
        $dto->getTo()->setDistrict($givenTransit->getTo()->getDistrict());



        $transitService = new TransitService(
            self::getContainer()->get(DriverRepository::class),
            self::getContainer()->get(TransitRepository::class),
            $clientRepository,
            $this->createMock(InvoiceGenerator::class),
            $this->createMock(DriverNotificationService::class),
            new DistanceCalculator(),
            self::getContainer()->get(DriverPositionRepository::class),
            self::getContainer()->get(DriverSessionRepository::class),
            self::getContainer()->get(CarTypeService::class),
            new GeocodingService(),
            self::getContainer()->get(AddressRepository::class),
            self::getContainer()->get(DriverFeeService::class), $clock,
            $this->createMock(AwardsService::class),
        );

        $this->expectNotToPerformAssertions();
        $transitService->createTransit($dto);
    }
}
