<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Integration;

use LegacyFighter\Cabs\Service\DriverService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ValidateDriverLicenceIntegrationTest extends KernelTestCase
{
    private DriverService $sut;

    /**
     * @test
     */
    public function canCreateActiveDriverWithValidLicenseNumber(): void
    {
        $createdDriver = $this->sut->createDriver(
            'FARME100165AB5EW',
            'Snow',
            'John',
            'regular',
            'inactive',
            null,
        );

        $loadedDriver = $this->sut->load($createdDriver->getId());

        self::assertSame('FARME100165AB5EW', $loadedDriver->getDriverLicense());
    }

    /**
     * @test
     */
    public function canChangeLicenseNumberToValidOne(): void
    {
        $createdDriver = $this->sut->createDriver(
            'FARME100165AB5EW',
            'Snow',
            'John',
            'regular',
            'active',
            null,
        );

        $this->sut->changeLicenseNumber('99999740614992TL', $createdDriver->getId());

        $loadedDriver = $this->sut->load($createdDriver->getId());

        self::assertSame('99999740614992TL', $loadedDriver->getDriverLicense());
    }

    public function canChangeDriverStatusToActiveIfItsLicenseIsValid(): void
    {
        $createdDriver = $this->sut->createDriver(
            'FARME100165AB5EW',
            'Snow',
            'John',
            'regular',
            'inactive',
            null,
        );

        $this->sut->changeDriverStatus($createdDriver->getId(), 'active');

        $loadedDriver = $this->sut->load($createdDriver->getId());

        self::assertSame('active', $loadedDriver->getStatus());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->getContainer()->get(DriverService::class);
    }
}
