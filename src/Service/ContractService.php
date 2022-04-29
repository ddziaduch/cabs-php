<?php

namespace LegacyFighter\Cabs\Service;

use LegacyFighter\Cabs\DTO\ContractAttachmentDTO;
use LegacyFighter\Cabs\DTO\ContractDTO;
use LegacyFighter\Cabs\Entity\Contract;
use LegacyFighter\Cabs\Repository\ContractAttachmentRepository;
use LegacyFighter\Cabs\Repository\ContractRepository;

class ContractService
{
    public function __construct(
        private ContractRepository $contractRepository,
        private ContractAttachmentRepository $contractAttachmentRepository
    ) {
    }

    public function createContract(
        string $partnerName,
        string $subject,
    ): int {
        $partnerContractsCount = count($this->contractRepository->findByPartnerName($partnerName)) + 1;

        $contract = new Contract($partnerName, $subject, $partnerContractsCount);

        return $this->contractRepository->save($contract)->getId();
    }

    public function acceptContract(int $id): void
    {
        $contract = $this->contractRepository->getOne($id);

        $contract->accept();

        $this->contractRepository->save($contract);
    }

    public function rejectContract(int $id): void
    {
        $contract = $this->contractRepository->getOne($id);

        $contract->reject();

        $this->contractRepository->save($contract);
    }

    public function rejectAttachment(int $attachmentId): void
    {
        $attachment = $this->contractAttachmentRepository->getOne($attachmentId);

        $attachment->reject();

        $this->contractAttachmentRepository->save($attachment);
    }

    public function acceptAttachment(int $attachmentId): void
    {
        $attachment = $this->contractAttachmentRepository->getOne($attachmentId);

        $attachment->accept();

        $this->contractAttachmentRepository->save($attachment);
    }

    public function findDto(int $id): ContractDTO
    {
        return ContractDTO::from($this->contractRepository->getOne($id));
    }

    public function proposeAttachment(int $contractId, string $data): ContractAttachmentDTO
    {
        $contract = $this->contractRepository->getOne($contractId);

        $attachment = $contract->proposeAttachment($data);

        $this->contractAttachmentRepository->save($attachment);

        return ContractAttachmentDTO::from($attachment);
    }

    public function removeAttachment(int $contractId, int $attachmentId): void
    {
        //TODO sprawdzenie czy nalezy do kontraktu (JIRA: II-14455)
        $this->contractAttachmentRepository->deleteById($attachmentId);
    }
}
