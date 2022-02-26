<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use LegacyFighter\Cabs\Common\BaseEntity;
use LegacyFighter\Cabs\Distance\Distance;
use LegacyFighter\Cabs\Money\Money;

#[Entity]
class Transit extends BaseEntity
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_WAITING_FOR_DRIVER_ASSIGNMENT = 'waiting-for-driver-assigment';
    public const STATUS_DRIVER_ASSIGNMENT_FAILED = 'driver-assigment-failed';
    public const STATUS_TRANSIT_TO_PASSENGER = 'transit-to-passenger';
    public const STATUS_IN_TRANSIT = 'in-transit';
    public const STATUS_COMPLETED = 'completed';

    public const DRIVER_PAYMENT_STATUS_NOT_PAID = 'not-paid';
    public const DRIVER_PAYMENT_STATUS_PAID = 'paid';
    public const DRIVER_PAYMENT_STATUS_CLAIMED = 'claimed';
    public const DRIVER_PAYMENT_STATUS_RETURNED = 'returned';

    public const CLIENT_PAYMENT_STATUS_NOT_PAID = 'not-paid';
    public const CLIENT_PAYMENT_STATUS_PAID = 'paid';
    public const CLIENT_PAYMENT_STATUS_RETURNED = 'returned';

    #[Column(nullable: true)]
    private ?string $driverPaymentStatus = null;

    #[Column(nullable: true)]
    private ?string $clientPaymentStatus = null;

    #[Column(nullable: true)]
    private ?string $paymentType = null;

    #[Column]
    private string $status;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $date = null;

    #[ManyToOne(targetEntity: Address::class)]
    private Address $from;

    #[ManyToOne(targetEntity: Address::class)]
    private Address $to;

    #[Column(type: 'integer')]
    private int $pickupAddressChangeCounter = 0;

    #[ManyToOne(targetEntity: Driver::class)]
    private ?Driver $driver = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $started = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completeAt = null;

    /**
     * @var Collection<Driver>
     */
    #[ManyToMany(targetEntity: Driver::class)]
    #[JoinTable(name: 'transit_driver_rejected')]
    private Collection $driversRejections;

    /**
     * @var Collection<Driver>
     */
    #[ManyToMany(targetEntity: Driver::class)]
    #[JoinTable(name: 'transit_driver_proposed')]
    private Collection $proposedDrivers;

    #[Column(type: 'integer')]
    private int $awaitingDriversResponses = 0;

    #[Embedded(class: Tariff::class)]
    private Tariff $tariff;

    #[Column(type: 'float', nullable: true)]
    private ?float $km;

    // https://stackoverflow.com/questions/37107123/sould-i-store-price-as-decimal-or-integer-in-mysql
    #[Column(type: 'money', nullable: true)]
    private ?Money $price = null;

    #[Column(type: 'money', nullable: true)]
    private ?Money $estimatedPrice = null;

    #[Column(type: 'money', nullable: true)]
    private ?Money $driversFee = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateTime;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $published = null;

    #[ManyToOne(targetEntity: Client::class)]
    private Client $client;

    #[Column(nullable: true)]
    private ?string $carType;

    public function __construct(
        Client $client,
        Address $from,
        Address $to,
        string $carClass,
        \DateTimeImmutable $dateTime,
        Distance $distance,
    ) {
        $this->client = $client;
        $this->from = $from;
        $this->to = $to;
        $this->carType = $carClass;
        $this->status = self::STATUS_DRAFT;
        $this->tariff = Tariff::ofTime($dateTime);
        $this->dateTime = $dateTime;
        $this->km = $distance->toKmInFloat();
        $this->proposedDrivers = new ArrayCollection();
        $this->driversRejections = new ArrayCollection();
    }

    public function proposeDriver(Driver $driver): void
    {
        $this->proposedDrivers->add($driver);
        ++$this->awaitingDriversResponses;
    }

    public function driverAssignmentFailed(): void
    {
        $this->status = self::STATUS_DRIVER_ASSIGNMENT_FAILED;
        $this->driver = null;
        $this->km = Distance::zero()->toKmInFloat();
        $this->estimateCost();
        $this->awaitingDriversResponses = 0;
    }

    public function publish(\DateTimeImmutable $published): void
    {
        $this->status = Transit::STATUS_WAITING_FOR_DRIVER_ASSIGNMENT;
        $this->published = $published;
    }

    public function cancel(): void
    {
        if (
            !in_array(
                $this->getStatus(),
                [
                    self::STATUS_DRAFT,
                    self::STATUS_WAITING_FOR_DRIVER_ASSIGNMENT,
                    self::STATUS_TRANSIT_TO_PASSENGER,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException('Transit cannot be cancelled, id = '.$this->id);
        }

        $this->status = self::STATUS_CANCELLED;
        $this->driver = null;
        $this->km = Distance::zero()->toKmInFloat();
        $this->estimateCost();
        $this->awaitingDriversResponses = 0;
    }

    public function changeDestination(Address $newAddress, Distance $newDistance): void
    {
        if ($this->status === self::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Address \'to\' cannot be changed, id = '.$this->id);
        }
        $this->to = $newAddress;
        $this->km = $newDistance->toKmInFloat();
        $this->estimateCost();
    }

    public function changePickupAddress(
        Address $newAddress,
        Distance $newDistance,
        float $distanceInKMeters,
    ): void {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Address \'from\' cannot be changed, id = '.$this->id);
        }

        if ($this->status === self::STATUS_WAITING_FOR_DRIVER_ASSIGNMENT) {
            throw new \InvalidArgumentException('Address \'from\' cannot be changed, id = '.$this->id);
        }

        if ($distanceInKMeters > 0.25) {
            throw new \InvalidArgumentException('Address \'from\' cannot be changed, id = '.$this->id);
        }

        if ($this->pickupAddressChangeCounter > 2) {
            throw new \InvalidArgumentException('Address \'from\' cannot be changed, id = '.$this->id);
        }

        $this->from = $newAddress;
        $this->km = $newDistance->toKmInFloat();
        $this->estimateCost();
        ++$this->pickupAddressChangeCounter;
    }

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, [
            self::STATUS_IN_TRANSIT,
            self::STATUS_TRANSIT_TO_PASSENGER,
            self::STATUS_DRIVER_ASSIGNMENT_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
            self::STATUS_WAITING_FOR_DRIVER_ASSIGNMENT,
            self::STATUS_DRAFT,
        ], true)) {
            throw new \InvalidArgumentException('Invalid driver status value');
        }
        $this->status = $status;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function getFrom(): Address
    {
        return $this->from;
    }

    public function getTo(): Address
    {
        return $this->to;
    }

    public function setTo(Address $to): void
    {
        $this->to = $to;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeImmutable $acceptedAt): void
    {
        $this->acceptedAt = $acceptedAt;
    }

    public function getStarted(): ?\DateTimeImmutable
    {
        return $this->started;
    }

    public function setStarted(?\DateTimeImmutable $started): void
    {
        $this->started = $started;
    }

    public function getDriversRejections(): array
    {
        return $this->driversRejections->toArray();
    }

    public function setDriversRejections(array $driversRejections): void
    {
        $this->driversRejections = new ArrayCollection($driversRejections);
    }

    public function getProposedDrivers(): array
    {
        return $this->proposedDrivers->toArray();
    }

    public function getAwaitingDriversResponses(): int
    {
        return $this->awaitingDriversResponses;
    }

    public function setAwaitingDriversResponses(int $awaitingDriversResponses): void
    {
        $this->awaitingDriversResponses = $awaitingDriversResponses;
    }

    public function getKm(): ?Distance
    {
        return $this->km === null ? null : Distance::ofKm($this->km);
    }

    public function setKm(Distance $km): void
    {
        $this->km = $km->toKmInFloat();
        $this->estimateCost();
    }

    public function getPrice(): ?Money
    {
        return $this->price;
    }

    //just for testing
    public function setPrice(?Money $price): void
    {
        $this->price = $price;
    }

    public function getEstimatedPrice(): ?Money
    {
        return $this->estimatedPrice;
    }

    public function getDriversFee(): ?Money
    {
        return $this->driversFee;
    }

    public function setDriversFee(?Money $driversFee): void
    {
        $this->driversFee = $driversFee;
    }

    public function getDateTime(): ?\DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function setDateTime(?\DateTimeImmutable $dateTime): void
    {
        $this->tariff = Tariff::ofTime($dateTime);
        $this->dateTime = $dateTime;
    }

    public function getPublished(): ?\DateTimeImmutable
    {
        return $this->published;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function estimateCost(): Money
    {
        if ($this->status === self::STATUS_COMPLETED) {
            throw new \RuntimeException('Estimating cost for completed transit is forbidden, id = ', $this->id);
        }

        $estimated = $this->calculateCost();
        $this->estimatedPrice = $estimated;
        $this->price = null;

        return $this->estimatedPrice;
    }

    public function calculateFinalCosts(): Money
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            throw new \RuntimeException('Cannot calculate final cost if the transit is not completed');
        }

        return $this->calculateCost();
    }

    private function calculateCost(): Money
    {
        $money = $this->tariff->calculateCost(Distance::ofKm($this->km));
        $this->price = $money;
        return $money;
    }

    public function getDriver(): ?Driver
    {
        return $this->driver;
    }

    public function setDriver(?Driver $driver): void
    {
        $this->driver = $driver;
    }

    public function getCompleteAt(): ?\DateTimeImmutable
    {
        return $this->completeAt;
    }

    public function setCompleteAt(?\DateTimeImmutable $completeAt): void
    {
        $this->completeAt = $completeAt;
    }

    public function getCarType(): ?string
    {
        return $this->carType;
    }

    public function getTariff(): Tariff
    {
        return $this->tariff;
    }

    public function isWaitingForDriverAssignment(): bool
    {
        return $this->getStatus() === Transit::STATUS_WAITING_FOR_DRIVER_ASSIGNMENT;
    }

    public function isAwaitingForDriversResponses(): bool
    {
        return $this->awaitingDriversResponses > 4;
    }

    public function isWaitingForDriverAssignmentTooLong(\DateTimeImmutable $now): bool
    {
        return $this->published->modify('+300 seconds') < $now || $this->status === self::STATUS_CANCELLED;
    }

    public function isDriverRejected(Driver $driver): bool
    {
        return $this->driversRejections->contains($driver);
    }
}
