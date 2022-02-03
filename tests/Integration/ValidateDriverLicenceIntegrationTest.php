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
     * @dataProvider invalidLicenseNumberDataProvider
     */
    public function cannotCreateActiveDriverWithInvalidLicenseNumber(string $invalidLicenseNumber): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Illegal license no = '.$invalidLicenseNumber);

        $this->sut->createDriver(
            $invalidLicenseNumber,
            'Snow',
            'John',
            'regular',
            'active',
            null,
        );
    }

    /**
     * @test
     * @dataProvider invalidLicenseNumberDataProvider
     */
    public function canCreateInactiveDriverWithInvalidLicenseNumber(string $invalidLicenseNumber): void
    {
        $createdDriver = $this->sut->createDriver(
            $invalidLicenseNumber,
            'Snow',
            'John',
            'regular',
            'inactive',
            null,
        );

        $loadedDriver = $this->sut->load($createdDriver->getId());

        self::assertSame($invalidLicenseNumber, $loadedDriver->getDriverLicense());
    }

    /**
     * @test
     * @dataProvider invalidLicenseNumberDataProvider
     */
    public function cannotChangeLicenseNumberToInvalidOne(string $invalidLicenseNumber): void
    {
        $createdDriver = $this->sut->createDriver(
            'AAAAA123456AA0AA',
            'Snow',
            'John',
            'regular',
            'active',
            null,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Illegal license no = '.$invalidLicenseNumber);

        $this->sut->changeLicenseNumber($invalidLicenseNumber, $createdDriver->getId());
    }

    /**
     * @test
     * @dataProvider invalidLicenseNumberDataProvider
     */
    public function cannotChangeDriverStatusIfItsLicenseIsInvalid(string $invalidLicenseNumber): void
    {
        $createdDriver = $this->sut->createDriver(
            $invalidLicenseNumber,
            'Snow',
            'John',
            'regular',
            'inactive',
            null,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Illegal license no = '.$invalidLicenseNumber);

        $this->sut->changeDriverStatus($createdDriver->getId(), 'active');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->getContainer()->get(DriverService::class);
    }

    public function invalidLicenseNumberDataProvider(): array
    {
        return [
            ['invalidLicenseNumber' => ''],
            ['invalidLicenseNumber' => 'invalid license number'],
        ];
    }
}
