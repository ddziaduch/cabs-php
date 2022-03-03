<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Integration;

use LegacyFighter\Cabs\DTO\CarTypeDTO;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Service\CarTypeService;
use LegacyFighter\Cabs\Tests\Common\PrivateProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CarTypeLifeCycleIntegrationTest extends KernelTestCase
{
    public function testCanBeCreated(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $actualCarType = $carTypeService->load(
            $carTypeService->create($givenCarTypeDto)->getId(),
        );

        self::assertSame($givenCarTypeDto->getStatus(), $actualCarType->getStatus());
        self::assertSame($givenCarTypeDto->getCarClass(), $actualCarType->getCarClass());
        self::assertSame($givenCarTypeDto->getActiveCarsCounter(), $actualCarType->getActiveCarsCounter());
        self::assertSame($givenCarTypeDto->getCarsCounter(), $actualCarType->getCarsCounter());
        self::assertSame($givenCarTypeDto->getDescription(), $actualCarType->getDescription());
        self::assertSame($givenCarTypeDto->getMinNoOfCarsToActivateClass(), $actualCarType->getMinNoOfCarsToActivateClass());
    }

    public function testCanChangeDescription(): void
    {
        $givenCarTypeDto1 = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $carTypeService->create($givenCarTypeDto1);
        $actualCarType1 = $carTypeService->load(
            $carTypeService->create($givenCarTypeDto1)->getId(),
        );
        $givenCarTypeDto2 = $this->aCarTypeDto('the super vans');
        $carTypeService->create($givenCarTypeDto2);
        $actualCarType2 = $carTypeService->load(
            $carTypeService->create($givenCarTypeDto2)->getId(),
        );

        self::assertSame($actualCarType1->getId(), $actualCarType2->getId());
        self::assertSame('the super vans', $actualCarType2->getDescription());

        self::assertSame($givenCarTypeDto1->getStatus(), $actualCarType2->getStatus());
        self::assertSame($givenCarTypeDto1->getCarClass(), $actualCarType2->getCarClass());
        self::assertSame($givenCarTypeDto1->getActiveCarsCounter(), $actualCarType2->getActiveCarsCounter());
        self::assertSame($givenCarTypeDto1->getCarsCounter(), $actualCarType2->getCarsCounter());
        self::assertSame($givenCarTypeDto1->getMinNoOfCarsToActivateClass(), $actualCarType2->getMinNoOfCarsToActivateClass());
    }

    public function testCanRegisterCar(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerCar($givenCarTypeDto->getCarClass());
        $carType = $carTypeService->load($id);
        self::assertSame(1, $carType->getCarsCounter());
        self::assertSame(0, $carType->getActiveCarsCounter());
    }

    public function testCanRegisterActiveCar(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerActiveCar($givenCarTypeDto->getCarClass());
        $carType = $carTypeService->load($id);
        self::assertSame(0, $carType->getCarsCounter());
        self::assertSame(1, $carType->getActiveCarsCounter());
    }

    public function testCanUnregisterCar(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerCar($givenCarTypeDto->getCarClass());
        $carTypeService->registerCar($givenCarTypeDto->getCarClass());
        $carTypeService->registerCar($givenCarTypeDto->getCarClass());
        $carTypeService->unregisterCar($givenCarTypeDto->getCarClass());
        $carType = $carTypeService->load($id);
        self::assertSame(2, $carType->getCarsCounter());
        self::assertSame(0, $carType->getActiveCarsCounter());
    }

    public function testCanUnregisterActiveCar(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerActiveCar($givenCarTypeDto->getCarClass());
        $carTypeService->registerActiveCar($givenCarTypeDto->getCarClass());
        $carTypeService->registerActiveCar($givenCarTypeDto->getCarClass());
        $carTypeService->unregisterActiveCar($givenCarTypeDto->getCarClass());
        $carType = $carTypeService->load($id);
        self::assertSame(0, $carType->getCarsCounter());
        self::assertSame(2, $carType->getActiveCarsCounter());
    }

    public function testCanBeActivated(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerCar($givenCarTypeDto->getCarClass());
        $carTypeService->activate($id);
        $carType = $carTypeService->load($id);
        self::assertSame(CarType::STATUS_ACTIVE, $carType->getStatus());
    }

    public function testCanNotBeActivatedIfNoMinRequiredCarsMeet(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $this->expectException(\RuntimeException::class);
        $carTypeService->activate($id);
    }

    public function testCanBeDeactivated(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerCar($givenCarTypeDto->getCarClass());
        $carTypeService->activate($id);
        $carTypeService->deactivate($id);
        $carType = $carTypeService->load($id);
        self::assertSame(CarType::STATUS_INACTIVE, $carType->getStatus());
    }

    private function aCarTypeDto(string $description): CarTypeDTO
    {
        $givenCarType = new CarType(
            CarType::CAR_CLASS_VAN, $description, 1,
        );
        PrivateProperty::setId(1, $givenCarType);

        return CarTypeDTO::new($givenCarType);
    }

    private function aCarTypeService(): CarTypeService
    {
        $carTypeService = self::getContainer()->get(CarTypeService::class);
        assert($carTypeService instanceof CarTypeService);

        return $carTypeService;
    }
}
