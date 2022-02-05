<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\VO;

class Money implements \Stringable
{
    private function __construct(private int $amount)
    {
    }

    public static function from(int $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(Money $other): Money
    {
        return new self($this->amount + $other->amount);
    }

    public function sub(Money $other): Money
    {
        return new self($this->amount - $other->amount);
    }

    public function percentage(int $percentage): Money
    {
        return new self((int) round($this->amount * $percentage / 100));
    }

    public function max(Money ...$others): Money
    {
        return Money::from(
            max(
                $this->amount,
                ...array_map(
                    fn (Money $other) => $other->amount,
                    $others,
                ),
            ),
        );
    }

    public function toInt(): int
    {
        return $this->amount;
    }

    public function __toString(): string
    {
        return sprintf('%.2f', $this->amount);
    }
}
