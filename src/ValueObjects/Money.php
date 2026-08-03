<?php

namespace AnjanTalukdar\GstInvoice\ValueObjects;

use AnjanTalukdar\GstInvoice\Enums\RoundingStrategy;
use JsonSerializable;

class Money implements JsonSerializable
{
    protected float $amount;

    public function __construct(float|int|string $amount)
    {
        $this->amount = (float) $amount;
    }

    public static function of(float|int|string $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0.00);
    }

    public function getAmount(): float
    {
        return round($this->amount, 2);
    }

    public function getRawAmount(): float
    {
        return $this->amount;
    }

    public function add(Money|float|int $other): self
    {
        $val = $other instanceof self ? $other->getRawAmount() : (float)$other;
        return new self($this->amount + $val);
    }

    public function subtract(Money|float|int $other): self
    {
        $val = $other instanceof self ? $other->getRawAmount() : (float)$other;
        return new self($this->amount - $val);
    }

    public function multiply(float|int $multiplier): self
    {
        return new self($this->amount * $multiplier);
    }

    public function percentage(float|int $rate): self
    {
        return new self($this->amount * ($rate / 100));
    }

    public function round(RoundingStrategy|string $strategy = RoundingStrategy::STANDARD, int $precision = 2): self
    {
        $strat = $strategy instanceof RoundingStrategy ? $strategy->value : (string)$strategy;

        $val = match ($strat) {
            'floor' => floor($this->amount * pow(10, $precision)) / pow(10, $precision),
            'ceil' => ceil($this->amount * pow(10, $precision)) / pow(10, $precision),
            'bankers' => round($this->amount, $precision, PHP_ROUND_HALF_EVEN),
            default => round($this->amount, $precision),
        };

        return new self($val);
    }

    public function equals(Money|float|int $other): bool
    {
        $val = $other instanceof self ? $other->getAmount() : round((float)$other, 2);
        return abs($this->getAmount() - $val) < 0.0001;
    }

    public function greaterThan(Money|float|int $other): bool
    {
        $val = $other instanceof self ? $other->getAmount() : round((float)$other, 2);
        return $this->getAmount() > $val;
    }

    public function isZero(): bool
    {
        return abs($this->getAmount()) < 0.0001;
    }

    public function formatted(string $symbol = '₹'): string
    {
        return $symbol . number_format($this->getAmount(), 2);
    }

    public function jsonSerialize(): float
    {
        return $this->getAmount();
    }

    public function __toString(): string
    {
        return (string) $this->getAmount();
    }
}
