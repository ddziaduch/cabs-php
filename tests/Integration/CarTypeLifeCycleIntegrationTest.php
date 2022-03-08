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
        $actualCarTypeDto = $carTypeService->loadDto(
            $carTypeService->create($givenCarTypeDto)->getId(),
        );

        self::assertSame($givenCarTypeDto->getStatus(), $actualCarTypeDto->getStatus());
        self::assertSame($givenCarTypeDto->getCarClass(), $actualCarTypeDto->getCarClass());
        self::assertSame($givenCarTypeDto->getActiveCarsCounter(), $actualCarTypeDto->getActiveCarsCounter());
        self::assertSame($givenCarTypeDto->getCarsCounter(), $actualCarTypeDto->getCarsCounter());
        self::assertSame($givenCarTypeDto->getDescription(), $actualCarTypeDto->getDescription());
        self::assertSame($givenCarTypeDto->getMinNoOfCarsToActivateClass(), $actualCarTypeDto->getMinNoOfCarsToActivateClass());
    }

    public function testCanChangeDescription(): void
    {
        $givenCarTypeDto1 = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $carTypeService->create($givenCarTypeDto1);
        $actualCarTypeDto1 = $carTypeService->loadDto(
            $carTypeService->create($givenCarTypeDto1)->getId(),
        );
        $givenCarTypeDto2 = $this->aCarTypeDto('the super vans');
        $carTypeService->create($givenCarTypeDto2);
        $actualCarTypeDto2 = $carTypeService->loadDto(
            $carTypeService->create($givenCarTypeDto2)->getId(),
        );

        self::assertSame($actualCarTypeDto1->getId(), $actualCarTypeDto2->getId());
        self::assertSame('the super vans', $actualCarTypeDto2->getDescription());

        self::assertSame($givenCarTypeDto1->getStatus(), $actualCarTypeDto2->getStatus());
        self::assertSame($givenCarTypeDto1->getCarClass(), $actualCarTypeDto2->getCarClass());
        self::assertSame($givenCarTypeDto1->getActiveCarsCounter(), $actualCarTypeDto2->getActiveCarsCounter());
        self::assertSame($givenCarTypeDto1->getCarsCounter(), $actualCarTypeDto2->getCarsCounter());
        self::assertSame($givenCarTypeDto1->getMinNoOfCarsToActivateClass(), $actualCarTypeDto2->getMinNoOfCarsToActivateClass());
    }

    public function testCanRegisterCar(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerCar($givenCarTypeDto->getCarClass());
        $carTypeDto = $carTypeService->loadDto($id);
        self::assertSame(1, $carTypeDto->getCarsCounter());
        self::assertSame(0, $carTypeDto->getActiveCarsCounter());
    }

    public function testCanRegisterActiveCar(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerActiveCar($givenCarTypeDto->getCarClass());
        $carTypeDto = $carTypeService->loadDto($id);
        self::assertSame(0, $carTypeDto->getCarsCounter());
        self::assertSame(1, $carTypeDto->getActiveCarsCounter());
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
        $carTypeDto = $carTypeService->loadDto($id);
        self::assertSame(2, $carTypeDto->getCarsCounter());
        self::assertSame(0, $carTypeDto->getActiveCarsCounter());
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
        $carTypeDto = $carTypeService->loadDto($id);
        self::assertSame(0, $carTypeDto->getCarsCounter());
        self::assertSame(2, $carTypeDto->getActiveCarsCounter());
    }

    public function testCanBeActivated(): void
    {
        $givenCarTypeDto = $this->aCarTypeDto('the vans');
        $carTypeService = $this->aCarTypeService();
        $id = $carTypeService->create($givenCarTypeDto)->getId();
        $carTypeService->registerCar($givenCarTypeDto->getCarClass());
        $carTypeService->activate($id);
        $carTypeDto = $carTypeService->loadDto($id);
        self::assertSame(CarType::STATUS_ACTIVE, $carTypeDto->getStatus());
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
        $carTypeDto = $carTypeService->loadDto($id);
        self::assertSame(CarType::STATUS_INACTIVE, $carTypeDto->getStatus());
    }

    private function aCarTypeDto(string $description): CarTypeDTO
    {
        $givenCarType = new CarType(
            CarType::CAR_CLASS_VAN, $description, 1,
        );
        PrivateProperty::setId(1, $givenCarType);

        return CarTypeDTO::new($givenCarType, 0);
    }

    private function aCarTypeService(): CarTypeService
    {
        $carTypeService = self::getContainer()->get(CarTypeService::class);
        assert($carTypeService instanceof CarTypeService);

        return $carTypeService;
    }
}
