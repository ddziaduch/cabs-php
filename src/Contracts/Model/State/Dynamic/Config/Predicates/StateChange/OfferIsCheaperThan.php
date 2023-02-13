<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Contracts\Model\State\Dynamic\Config\Predicates\StateChange;

use LegacyFighter\Cabs\Contracts\Model\State\Dynamic\ChangeCommand;
use LegacyFighter\Cabs\Contracts\Model\State\Dynamic\State;

final class OfferIsCheaperThan implements Predicate
{
    public function __construct(private int $amount)
    {
    }

    public function test(State $state, ChangeCommand $command): bool
    {
        return $state->getDocumentHeader()->getPrice() < $this->amount;
    }
}
