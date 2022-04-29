<?php

namespace LegacyFighter\Cabs\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use LegacyFighter\Cabs\Common\BaseEntity;

#[Entity]
class Contract extends BaseEntity
{
    public const STATUS_NEGOTIATIONS_IN_PROGRESS = 'negotiations-in-progress';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACCEPTED = 'accepted';

    /**
     * @var Collection<ContractAttachment>
     */
    #[OneToMany(mappedBy: 'contract', targetEntity: ContractAttachment::class)]
    private Collection $attachments;

    #[Column]
    private string $partnerName;

    #[Column]
    private string $subject;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $creationDate;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rejectedAt = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $changeDate = null;

    #[Column]
    private string $status = self::STATUS_NEGOTIATIONS_IN_PROGRESS;

    #[Column]
    private string $contractNo;

    public function __construct(
        string $partnerName,
        string $subject,
        int $partnerContractsCount,
    ) {
        $this->attachments = new ArrayCollection();
        $this->creationDate = new \DateTimeImmutable();

        $this->partnerName = $partnerName;
        $this->subject = $subject;
        $this->contractNo = sprintf('C/%s/%s', $partnerContractsCount, $partnerName);
    }

    public function getAttachments(): array
    {
        return $this->attachments->toArray();
    }

    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    public function getSubject(): string
    {
        return $this->subject;
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

    public function getContractNo(): string
    {
        return $this->contractNo;
    }

    public function accept(): void
    {
        foreach ($this->attachments as $attachment) {
            if ($attachment->getStatus() !== ContractAttachment::STATUS_ACCEPTED_BY_BOTH_SIDES) {
                throw new \RuntimeException('Not all attachments accepted by both sides');
            }
        }

        $this->status = self::STATUS_ACCEPTED;
    }

    public function reject(): void
    {
        $this->status = self::STATUS_REJECTED;
    }

    public function proposeAttachment(string $data): ContractAttachment
    {
        if ($this->status !== self::STATUS_NEGOTIATIONS_IN_PROGRESS) {
            throw new \RuntimeException('Contract must be still in negotiation in order to propose attachment');
        }

        $attachment = new ContractAttachment($this, $data);

        $this->attachments->add($attachment);

        return $attachment;
    }
}
