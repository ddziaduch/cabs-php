<?php

namespace LegacyFighter\Cabs\Tests\Common;

use LegacyFighter\Cabs\Distance\Distance;
use LegacyFighter\Cabs\DTO\AddressDTO;
use LegacyFighter\Cabs\DTO\CarTypeDTO;
use LegacyFighter\Cabs\DTO\TransitDTO;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Driver;
use LegacyFighter\Cabs\Entity\DriverFee;
use LegacyFighter\Cabs\Entity\Transit;
use LegacyFighter\Cabs\Money\Money;
use LegacyFighter\Cabs\Repository\AddressRepository;
use LegacyFighter\Cabs\Repository\ClientRepository;
use LegacyFighter\Cabs\Repository\DriverFeeRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;
use LegacyFighter\Cabs\Service\CarTypeService;
use LegacyFighter\Cabs\Service\DriverService;

class Fixtures
{
    private TransitRepository $transitRepository;
    private DriverFeeRepository $feeRepository;
    private DriverService $driverService;
    private AddressRepository $addressRepository;
    private ClientRepository $clientRepository;
    private CarTypeService $carTypeService;

    public function __construct(
        TransitRepository $transitRepository,
        DriverFeeRepository $feeRepository,
        DriverService $driverService,
        AddressRepository $addressRepository,
        ClientRepository $clientRepository,
        CarTypeService $carTypeService
    )
    {
        $this->transitRepository = $transitRepository;
        $this->feeRepository = $feeRepository;
        $this->driverService = $driverService;
        $this->addressRepository = $addressRepository;
        $this->clientRepository = $clientRepository;
        $this->carTypeService = $carTypeService;
    }


    public function aClient(): Client
    {
        $client = new Client();
        $client->setName('Janusz');
        $client->setLastName('Kowalski');
        $client->setType(Client::TYPE_NORMAL);
        $client->setDefaultPaymentType(Client::PAYMENT_TYPE_MONTHLY_INVOICE);
        return $this->clientRepository->save($client);
    }

    public function aDriver(): Driver
    {
        return $this->driverService->createDriver('FARME100165AB5EW', 'Kowalski', 'Janusz', Driver::TYPE_REGULAR, Driver::STATUS_ACTIVE, '');
    }

    public function driverHasFee(Driver $driver, string $feeType, int $amount, ?int $min = null): DriverFee
    {
        $driverFee = new DriverFee($feeType, $driver, $amount, $min === null ? Money::zero() : Money::from($min));
        return $this->feeRepository->save($driverFee);
    }

    public function aTransit(
        ?Driver $driver,
        int $price,
        ?\DateTimeImmutable $when = null,
        ?Client $client = null,
        ?Address $from = null,
        ?Address $to = null,
    ): Transit {
        $transit = new Transit(
            $client ?? $this->aClient(),
            $from ?? $this->anAddress('Polska', 'Warszawa', 'Młynarska', 20),
            $to ?? $this->anAddress('Polska', 'Warszawa', 'Zytnia', 20),
            CarType::CAR_CLASS_VAN,
            $when ?? new \DateTimeImmutable(),
            Distance::zero(),
        );
        $transit->setPrice(Money::from($price));
        if ($driver !== null) {
            $transit->proposeDriver($driver);
            $transit->accept($driver, $when ?? new \DateTimeImmutable());
        }

        return $this->transitRepository->save($transit);
    }

    public function aCompletedTransitAt(int $price, \DateTimeImmutable $when): Transit
    {
        $transit = $this->aTransit(
            $this->aDriver(),
            $price,
            $when,
            $this->aClient(),
            $this->anAddress('Polska', 'Warszawa', 'Młynarska', 20),
            $this->anAddress('Polska', 'Warszawa', 'Zytnia', 20),
        );
        $transit->complete($transit->getTo(), $transit->getKm(), new \DateTimeImmutable());

        return $this->transitRepository->save($transit);
    }

    public function anActiveCarCategory(string $carClass): CarType
    {
        $carType = new CarType($carClass, 'opis', 1);
        PrivateProperty::setId(1, $carType);
        $carTypeDTO = CarTypeDTO::new($carType, 0);
        $carType = $this->carTypeService->create($carTypeDTO);
        $this->carTypeService->registerCar($carClass);
        $this->carTypeService->activate($carType->getId());
        return $carType;
    }

    public function aTransitDTOWith(Client $client, AddressDTO $from, AddressDTO $to): TransitDTO
    {
        $transit = new Transit(
            $client,
            $from->toAddressEntity(),
            $to->toAddressEntity(),
            CarType::CAR_CLASS_VAN,
            new \DateTimeImmutable(),
            Distance::zero(),
        );
        PrivateProperty::setId(1, $transit);

        return TransitDTO::from($transit);
    }

    public function aTransitDTO(AddressDTO $from, AddressDTO $to): TransitDTO
    {
        return $this->aTransitDTOWith($this->aClient(), $from, $to);
    }

    public function anAddressDTO(string $country, string $city, string $street, int $buildingNumber): AddressDTO
    {
        $address = new Address($country, $city, $street, $buildingNumber);
        $address->setPostalCode('11-111');
        $address->setName('name');
        $address->setDistrict('district');
        return AddressDTO::from($address);
    }

    private function anAddress(string $country, string $city, string $street, int $buildingNumber): Address
    {
        $address = new Address($country, $city, $street, $buildingNumber);
        $address->setPostalCode('11-111');
        $address->setName('Home');
        $address->setDistrict('district');
        return $this->addressRepository->save($address);
    }
}
