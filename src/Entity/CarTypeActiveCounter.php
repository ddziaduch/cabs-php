<?php

namespace LegacyFighter\Cabs\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use LegacyFighter\Cabs\Common\BaseEntity;

#[Entity]
class CarTypeActiveCarsCounter extends BaseEntity
{
    #[Column]
    private string $carClass;

    #[Column(type: 'integer')]
    private int $activeCarsCounter = 0;

    public function getActiveCarsCounter(): int
    {
        return $this->activeCarsCounter;
    }
}
