<?php

namespace LegacyFighter\Cabs\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use LegacyFighter\Cabs\Common\BaseEntity;

#[Entity]
class ContractAttachment extends BaseEntity
{
    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_ACCEPTED_BY_ONE_SIDE = 'accepted-by-one-side';
    public const STATUS_ACCEPTED_BY_BOTH_SIDES = 'accepted-by-both-side';
    public const STATUS_REJECTED = 'rejected';

    #[Column(type: 'text')]
    private string $data;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $creationDate;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rejectedAt = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $changeDate = null;

    #[Column]
    private string $status = self::STATUS_PROPOSED;

    #[ManyToOne(targetEntity: Contract::class)]
    private Contract $contract;

    public function __construct(Contract $contract, string $data)
    {
        $this->creationDate = new \DateTimeImmutable();
        $this->contract = $contract;
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getCreationDate(): \DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function getRejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function getChangeDate(): ?\DateTimeImmutable
    {
        return $this->changeDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getContract(): Contract
    {
        return $this->contract;
    }

    public function reject(): void
    {
        $this->status = self::STATUS_REJECTED;
    }

    public function accept(): void
    {
        if (in_array($this->status, [self::STATUS_ACCEPTED_BY_ONE_SIDE, self::STATUS_ACCEPTED_BY_BOTH_SIDES], true)) {
            $this->status = self::STATUS_ACCEPTED_BY_BOTH_SIDES;
        } else {
            $this->status = self::STATUS_ACCEPTED_BY_ONE_SIDE;
        }
    }
}
