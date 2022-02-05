<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Unit;

use LegacyFighter\Cabs\VO\DriverLicense;
use PHPUnit\Framework\TestCase;

class DriverLicenseTest extends TestCase
{
    /**
     * @test
     * @dataProvider invalidLicenseNumberDataProvider
     */
    public function canCreateWithoutValidation(string $invalidLicenseNumber): void
    {
        $this->expectNotToPerformAssertions();

        DriverLicense::withoutValidation($invalidLicenseNumber);
    }

    public function cannotCreateWithInvalidLicenseNumber(string $invalidLicenseNumber): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Illegal license no = '.$invalidLicenseNumber);

        DriverLicense::withLicense($invalidLicenseNumber);
    }

    public function invalidLicenseNumberDataProvider(): array
    {
        return [
            ['invalidLicenseNumber' => ''],
            ['invalidLicenseNumber' => 'invalid license number'],
        ];
    }
}
