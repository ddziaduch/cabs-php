<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Contracts\Model\State\Dynamic\Task;

use LegacyFighter\Cabs\Contracts\Model\State\Dynamic\Config\Predicates\StateChange\OfferIsCheaperThan;
use LegacyFighter\Cabs\Contracts\Model\State\Dynamic\StateBuilder;
use LegacyFighter\Cabs\Contracts\Model\State\Dynamic\StateConfig;

final class TaskContractStateAssembler
{

    public const PRE_OFFER = 'pre-offer';
    public const DISCOUNTED = 'discounted';
    public const REJECTED_BY_MANAGER = 'rejected_by_manager';
    public const APPROVED_BY_MANAGER = 'approved_by_manager';
    public const REJECTED_BY_CLIENT = 'rejected_by_client';
    public const ACCEPTED_BY_CLIENT = 'accepted_by_client';
    public const SOLD = 'sold';

    public function __construct(private int $amount)
    {
    }

    public function assemble(): StateConfig
    {
        $builder = new StateBuilder();
        $builder->beginWith(self::PRE_OFFER);

        // OPTIONAL
        $builder->from(self::PRE_OFFER)->to(self::DISCOUNTED);

        // FINAL
        $builder->from(self::PRE_OFFER)->to(self::REJECTED_BY_MANAGER);
        $builder->from(self::DISCOUNTED)->to(self::REJECTED_BY_MANAGER);

        $builder->from(self::PRE_OFFER)->to(self::APPROVED_BY_MANAGER);
        $builder->from(self::DISCOUNTED)->to(self::APPROVED_BY_MANAGER);

        // FINAL
        $builder->from(self::APPROVED_BY_MANAGER)->to(self::REJECTED_BY_CLIENT);
        $builder->from(self::APPROVED_BY_MANAGER)->to(self::ACCEPTED_BY_CLIENT);

        $builder->from(self::APPROVED_BY_MANAGER)->to(self::SOLD);
        $builder->from(self::ACCEPTED_BY_CLIENT)->to(self::SOLD);

        // POSSIBLE ONLY WHEN OFFER IS CHEAPER THAN
        $predicate = new OfferIsCheaperThan($this->amount);
        $builder->from(self::PRE_OFFER)->check($predicate)->to(self::ACCEPTED_BY_CLIENT);
        $builder->from(self::PRE_OFFER)->check($predicate)->to(self::SOLD);
        $builder->from(self::DISCOUNTED)->check($predicate)->to(self::ACCEPTED_BY_CLIENT);
        $builder->from(self::DISCOUNTED)->check($predicate)->to(self::SOLD);
    }
}
