<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Integration;

use LegacyFighter\Cabs\DTO\TransitDTO;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Repository\AddressRepository;
use LegacyFighter\Cabs\Repository\ClientRepository;
use LegacyFighter\Cabs\Repository\DriverPositionRepository;
use LegacyFighter\Cabs\Repository\DriverRepository;
use LegacyFighter\Cabs\Repository\DriverSessionRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;
use LegacyFighter\Cabs\Service\AwardsService;
use LegacyFighter\Cabs\Service\CarTypeService;
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

    private ClientRepository $clientRepository;
    private TransitService $transitService;

    /** @test */
    public function canBeCreated(): void
    {
        $transit = $this->getTransit(1);
        $transit->setFrom($this->getAddress());
        $transit->setTo($this->getAddress());

        $client = $this->getClient();
        $this->clientRepository->save($client);

        $transit->setClient($client);
        $transit->setCarType(CarType::CAR_CLASS_ECO);

        $dto = TransitDTO::from($transit);
        $dto->getFrom()->setDistrict($transit->getFrom()->getDistrict());
        $dto->getTo()->setDistrict($transit->getTo()->getDistrict());

        $this->expectNotToPerformAssertions();
        $this->transitService->createTransit($dto);
    }

    /** @test */
    public function canNotBeCreatedWhenDriverDoesNotExist(): void
    {
        $transit = $this->getTransit(1);
        $transit->setFrom($this->getAddress());
        $transit->setTo($this->getAddress());

        $client = $this->getClient(1);

        $transit->setClient($client);
        $transit->setCarType(CarType::CAR_CLASS_ECO);

        $dto = TransitDTO::from($transit);
        $dto->getFrom()->setDistrict($transit->getFrom()->getDistrict());
        $dto->getTo()->setDistrict($transit->getTo()->getDistrict());

        $this->expectException(\InvalidArgumentException::class);
        $this->transitService->createTransit($dto);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRepository = self::getContainer()->get(ClientRepository::class);

        $this->transitService = new TransitService(
            self::getContainer()->get(DriverRepository::class),
            self::getContainer()->get(TransitRepository::class),
            $this->clientRepository,
            $this->createMock(InvoiceGenerator::class),
            $this->createMock(DriverNotificationService::class),
            new DistanceCalculator(),
            self::getContainer()->get(DriverPositionRepository::class),
            self::getContainer()->get(DriverSessionRepository::class),
            self::getContainer()->get(CarTypeService::class),
            new GeocodingService(),
            self::getContainer()->get(AddressRepository::class),
            self::getContainer()->get(DriverFeeService::class),
            $this->getClock(),
            $this->createMock(AwardsService::class),
        );
    }
}
