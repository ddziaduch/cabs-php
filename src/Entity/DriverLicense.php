<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Entity;

use Doctrine\ORM\Mapping\Embeddable;

#[Embeddable]
class DriverLicense implements \Stringable
{
    private const DRIVER_LICENSE_REGEX = '/^[A-Z9]{5}\d{6}[A-Z9]{2}\d[A-Z]{2}$/';

    private function __construct(private string $license)
    {
    }

    public static function withLicense(string $license): self
    {
        if ($license === '' || preg_match(self::DRIVER_LICENSE_REGEX, $license) !== 1) {
            throw new \InvalidArgumentException('Illegal license no = '.$license);
        }

        return new self($license);
    }

    public static function withoutValidation(string $license): self
    {
        return new self($license);
    }

    public function __toString(): string
    {
        return $this->license;
    }
}
