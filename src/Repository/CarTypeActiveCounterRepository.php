<?php

namespace LegacyFighter\Cabs\Repository;

use Doctrine\ORM\EntityManagerInterface;
use LegacyFighter\Cabs\Entity\CarType;

class CarTypeActiveCarsCounterRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function findByCarClass(string $carClass): ?CarType
    {
        return $this->em->getRepository(CarType::class)->findOneBy(['carClass' => $carClass]);
    }

    public function save(CarType $carType): void
    {
        $this->em->persist($carType);
        $this->em->flush();
    }

    public function delete(CarType $carType): void
    {
        $this->em->remove($carType);
        $this->em->flush();
    }

    public function incrementCounter(string $carClass): void
    {
        $this->em->getConnection()->executeQuery(
            <<<'SQL'
            UPDATE car_type_active_counter
            SET counter = counter + 1
            WHERE car_class = :carClass
            SQL,
            ['carClass' => $carClass],
        );
    }

    public function decrementCounter(string $carClass): void
    {
        $this->em->getConnection()->executeQuery(
            <<<'SQL'
            UPDATE car_type_active_counter
            SET counter = counter - 1
            WHERE car_class = :carClass
            SQL,
            ['carClass' => $carClass],
        );
    }
}
