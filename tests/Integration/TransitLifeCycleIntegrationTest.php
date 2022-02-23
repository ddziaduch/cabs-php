<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Integration;

use LegacyFighter\Cabs\DTO\AddressDTO;
use LegacyFighter\Cabs\DTO\CarTypeDTO;
use LegacyFighter\Cabs\DTO\TransitDTO;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Driver;
use LegacyFighter\Cabs\Entity\Transit;
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
use LegacyFighter\Cabs\Service\DriverService;
use LegacyFighter\Cabs\Service\DriverSessionService;
use LegacyFighter\Cabs\Service\DriverTrackingService;
use LegacyFighter\Cabs\Service\GeocodingService;
use LegacyFighter\Cabs\Service\InvoiceGenerator;
use LegacyFighter\Cabs\Service\TransitService;
use LegacyFighter\Cabs\Tests\WithFixtures;
use LegacyFighter\Cabs\VO\Distance;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TransitLifeCycleIntegrationTest extends KernelTestCase
{
    use WithFixtures;

    private TransitService $transitService;

    private ClientRepository $clientRepository;

    /** @test */
    public function canBeCreated(): void
    {
        $client = $this->createClient();
        $dto = $this->getTransitDto($client);
        $transit = $this->transitService->createTransit($dto);
        self::assertSame($client, $transit->getClient());
        // fix for inconsistency
        $actualFromDto = AddressDTO::from($transit->getFrom());
        $actualFromDto->setDistrict($dto->getFrom()->getDistrict());
        $actualToDto = AddressDTO::from($transit->getTo());
        $actualToDto->setDistrict($dto->getTo()->getDistrict());
        self::assertEquals($dto->getFrom(), $actualFromDto);
        self::assertEquals($dto->getTo(), $actualToDto);
        self::assertSame($dto->getCarClass(), $transit->getCarType());
        self::assertSame(Transit::STATUS_DRAFT, $transit->getStatus());
        self::assertEquals($dto->getDateTime(), $transit->getDateTime());
        self::assertEquals(Distance::ofKm(10.0), $transit->getKm());
    }

    /**
     * @test
     */
    public function canChangeAddressTo(): void
    {
        $transit = $this->createTransit();
        $newDestination = $this->getAddress('Sopot', 'Malczewskiego', 13);
        $this->transitService->changeTransitAddressToNew($transit->getId(), $newDestination);
        $transitWithNewDestination = $this->transitService->loadTransit($transit->getId());
        self::assertEquals(AddressDTO::from($newDestination), $transitWithNewDestination->getTo());
        self::assertEquals(Distance::ofKm(10.0), $transit->getKm());
    }

    /**
     * @test
     */
    public function canChangePickupPlace(): void
    {
        $transit = $this->createTransit();
        $newPickupPlace = $this->getAddress('Sopot', 'Malczewskiego', 13);
        $this->transitService->changeTransitAddressFromNew($transit->getId(), $newPickupPlace);
        $transitWithNewPickupPlace = $this->transitService->loadTransit($transit->getId());
        self::assertEquals(AddressDTO::from($newPickupPlace), $transitWithNewPickupPlace->getFrom());
        self::assertEquals(Distance::ofKm(10.0), $transit->getKm());
    }

    /** @test */
    public function canNotChangeAddressToWhenIsCompleted(): void
    {
        $transit = $this->createTransit();
        $transit->setStatus(Transit::STATUS_COMPLETED);
        self::getContainer()->get(TransitRepository::class)->save($transit);
        $newDestination = $this->getAddress('Sopot', 'Malczewskiego', 13);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Address \'to\' cannot be changed, id = '.$transit->getId());
        $this->transitService->changeTransitAddressToNew($transit->getId(), $newDestination);
    }

    /** @test */
    public function canBePublished(): void
    {
        $this->createNearByDriver();
        $transit = $this->createTransit();
        $this->transitService->publishTransit($transit->getId());
        $publishedTransit = $this->transitService->loadTransit($transit->getId());
        self::assertSame(Transit::STATUS_WAITING_FOR_DRIVER_ASSIGNMENT, $publishedTransit->getStatus());
    }

    /** @test */
    public function canBeAccepted(): void
    {
        $driver = $this->createNearByDriver();
        $transit = $this->createTransit();
        $this->transitService->publishTransit($transit->getId());
        $this->transitService->acceptTransit($driver->getId(), $transit->getId());
        $publishedTransit = $this->transitService->loadTransit($transit->getId());
        self::assertSame(Transit::STATUS_TRANSIT_TO_PASSENGER, $publishedTransit->getStatus());
        self::assertSame($driver, $transit->getDriver());
        self::assertSame(0, $transit->getAwaitingDriversResponses());
    }

    /** @test */
    public function canNotBeAcceptedByDriverWhoRejectedFirst(): void
    {
        $driver = $this->createNearByDriver();
        $transit = $this->createTransit();
        $this->transitService->publishTransit($transit->getId());
        $this->transitService->rejectTransit($driver->getId(), $transit->getId());
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('"Driver out of possible drivers, id = '.$driver->getId());
        $this->transitService->acceptTransit($driver->getId(), $transit->getId());
    }

    /** @test */
    public function canBeAcceptedByOnlyOneDriver(): void
    {
        $driver1 = $this->createNearByDriver();
        $driver2 = $this->createAnotherDriver();
        $transit = $this->createTransit();
        $this->transitService->publishTransit($transit->getId());
        $this->transitService->acceptTransit($driver1->getId(), $transit->getId());
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transit already accepted, id = '.$transit->getId());
        $this->transitService->acceptTransit($driver2->getId(), $transit->getId());
    }

    /** @test */
    public function canBeStarted(): void
    {
        $driver = $this->createNearByDriver();
        $transit = $this->createTransit();
        $this->transitService->publishTransit($transit->getId());
        $this->transitService->acceptTransit($driver->getId(), $transit->getId());
        $this->transitService->startTransit($driver->getId(), $transit->getId());
        $publishedTransit = $this->transitService->loadTransit($transit->getId());
        self::assertSame(Transit::STATUS_IN_TRANSIT, $publishedTransit->getStatus());
    }

    public function canBeCompleted(): void
    {
        $driver = $this->createNearByDriver();
        $transit = $this->createTransit();
        $this->transitService->publishTransit($transit->getId());
        $this->transitService->acceptTransit($driver->getId(), $transit->getId());
        $this->transitService->startTransit($driver->getId(), $transit->getId());
        $publishedTransit = $this->transitService->loadTransit($transit->getId());
        self::assertSame(Transit::STATUS_COMPLETED, $publishedTransit->getStatus());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRepository = self::getContainer()->get(ClientRepository::class);

        $carType = new class() extends CarType {
            public function __construct()
            {
                parent::__construct(CarType::CAR_CLASS_ECO, 'eco cars', 1);
                $this->id = 1;
            }
        };
        $carTypeService = self::getContainer()->get(CarTypeService::class);
        $carType = $carTypeService->create(CarTypeDTO::new($carType));
        $carTypeService->registerCar(CarType::CAR_CLASS_ECO);
        $carTypeService->activate($carType->getId());

        $this->transitService = new TransitService(
            self::getContainer()->get(DriverRepository::class),
            self::getContainer()->get(TransitRepository::class), $this->clientRepository,
            $this->createMock(InvoiceGenerator::class),
            $this->createMock(DriverNotificationService::class),
            new class() extends DistanceCalculator {
                public function calculateByMap(
                    float $latitudeFrom,
                    float $longitudeFrom,
                    float $latitudeTo,
                    float $longitudeTo
                ): float {
                    return 10.0;
                }

                public function calculateByGeo(
                    float $latitudeFrom,
                    float $longitudeFrom,
                    float $latitudeTo,
                    float $longitudeTo
                ): float {
                    return 20.0;
                }

            },
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

    private function getTransitDto(Client $client): TransitDTO
    {
        $transit = $this->getTransit(1);
        $transit->setFrom($this->getAddress());
        $transit->setTo($this->getAddress());

        $transit->setClient($client);

        $dto = TransitDTO::from($transit);
        $dto->getFrom()->setDistrict($transit->getFrom()->getDistrict());
        $dto->getTo()->setDistrict($transit->getTo()->getDistrict());

        return $dto;
    }

    private function createClient(): Client
    {
        $client = $this->getClient();
        $this->clientRepository->save($client);

        return $client;
    }

    private function createTransit(): Transit
    {
        $client = $this->createClient();
        $dto = $this->getTransitDto($client);
        $transit = $this->transitService->createTransit($dto);

        return $transit;
    }

    private function createNearByDriver(): Driver
    {
        $driverService = self::getContainer()->get(DriverService::class);

        $driver = $driverService->createDriver('FARME100165AB5EW', 'John', 'Smith', 'regular', 'active', null);

        $driverService->changeDriverStatus($driver->getId(), Driver::STATUS_ACTIVE);

        self::getContainer()->get(DriverSessionService::class)->logIn(
            $driver->getId(),
            'GA 12345',
            CarType::CAR_CLASS_ECO,
            'Skoda'
        );

        self::getContainer()->get(DriverTrackingService::class)->registerPosition(
            $driver->getId(),
            1,
            1
        );

        return $driver;
    }

    private function createAnotherDriver(): Driver
    {
        $driverService = self::getContainer()->get(DriverService::class);

        $driver = $driverService->createDriver('FARME100165AB5AB', 'Jan', 'Kowalski', 'regular', 'active', null);

        $driverService->changeDriverStatus($driver->getId(), Driver::STATUS_ACTIVE);

        self::getContainer()->get(DriverSessionService::class)->logIn(
            $driver->getId(),
            'GD 56789',
            CarType::CAR_CLASS_ECO,
            'Dacia'
        );

        self::getContainer()->get(DriverTrackingService::class)->registerPosition(
            $driver->getId(),
            1000,
            1000
        );

        return $driver;
    }
}
