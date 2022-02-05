<?php

namespace LegacyFighter\Cabs\Service;

use LegacyFighter\Cabs\Entity\DriverFee;
use LegacyFighter\Cabs\Repository\DriverFeeRepository;
use LegacyFighter\Cabs\Repository\TransitRepository;
use LegacyFighter\Cabs\VO\Money;

class DriverFeeService
{
    private DriverFeeRepository $driverFeeRepository;
    private TransitRepository $transitRepository;

    public function __construct(DriverFeeRepository $driverFeeRepository, TransitRepository $transitRepository)
    {
        $this->driverFeeRepository = $driverFeeRepository;
        $this->transitRepository = $transitRepository;
    }

    public function calculateDriverFee(int $transitId): int
    {
        $transit = $this->transitRepository->getOne($transitId);
        if($transit === null) {
            throw new \InvalidArgumentException('transit does not exist, id = '.$transitId);
        }
        if($transit->getDriversFee() !== null) {
            return $transit->getDriversFee()->toInt();
        }
        $transitPrice = $transit->getPrice();
        $driverFee = $this->driverFeeRepository->findByDriver($transit->getDriver());
        if($driverFee === null) {
            throw new \InvalidArgumentException('driver Fees not defined for driver, driver id = '.$transit->getDriver()->getId());
        }
        if($driverFee->getType() === DriverFee::TYPE_FLAT) {
            $finalFee = $transitPrice->sub(Money::from($driverFee->getAmount()));
        } else {
            $finalFee = $transitPrice->percentage($driverFee->getAmount());
        }

        return max($finalFee->toInt(), $driverFee->getMin() === null ? 0 : $driverFee->getMin()->toInt());
    }
}
