<?php

namespace AnjanTalukdar\GstInvoice\Helpers;

use AnjanTalukdar\GstInvoice\Enums\RoundingStrategy;

class RoundingHelper
{
    public static function round(float $value, RoundingStrategy|string $strategy = RoundingStrategy::STANDARD, int $precision = 2): float
    {
        $strat = $strategy instanceof RoundingStrategy ? $strategy->value : (string)$strategy;

        return match ($strat) {
            'floor' => floor($value * pow(10, $precision)) / pow(10, $precision),
            'ceil' => ceil($value * pow(10, $precision)) / pow(10, $precision),
            'bankers' => round($value, $precision, PHP_ROUND_HALF_EVEN),
            default => round($value, $precision),
        };
    }
}
