<?php

namespace AnjanTalukdar\GstInvoice\Helpers;

use InvalidArgumentException;

class NumberToWords
{
    /**
     * Convert currency amount to words in Indian Rupee format.
     */
    public static function toWords(float $amount, string $currencyCode = 'INR'): string
    {
        if (strtoupper($currencyCode) !== 'INR') {
            throw new InvalidArgumentException("NumberToWords currently supports INR currency format only. Received '{$currencyCode}'.");
        }

        $amount = round($amount, 2);
        if ($amount < 0) {
            return 'Minus ' . self::toWords(abs($amount), $currencyCode);
        }

        $number = (int) floor($amount);
        $fraction = (int) round(($amount - $number) * 100);

        $words = [];
        $lookup = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
            20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        ];

        if ($number === 0) {
            $words[] = 'Zero';
        } else {
            $crore = (int) floor($number / 10000000);
            $number %= 10000000;

            $lakh = (int) floor($number / 100000);
            $number %= 100000;

            $thousand = (int) floor($number / 1000);
            $number %= 1000;

            $hundred = (int) floor($number / 100);
            $number %= 100;

            if ($crore > 0) {
                $words[] = self::convertChunk($crore, $lookup) . ' Crore';
            }
            if ($lakh > 0) {
                $words[] = self::convertChunk($lakh, $lookup) . ' Lakh';
            }
            if ($thousand > 0) {
                $words[] = self::convertChunk($thousand, $lookup) . ' Thousand';
            }
            if ($hundred > 0) {
                $words[] = self::convertChunk($hundred, $lookup) . ' Hundred';
            }
            if ($number > 0) {
                $words[] = self::convertChunk($number, $lookup);
            }
        }

        $result = implode(' ', array_filter($words));

        if ($fraction > 0) {
            $result .= ' and ' . self::convertChunk($fraction, $lookup) . ' Paise';
        }

        return 'Rupees ' . $result . ' Only';
    }

    private static function convertChunk(int $num, array $lookup): string
    {
        if ($num < 20) {
            return $lookup[$num];
        }
        if ($num < 100) {
            $tens = (int) floor($num / 10) * 10;
            $ones = $num % 10;
            return trim($lookup[$tens] . ($ones > 0 ? ' ' . $lookup[$ones] : ''));
        }

        $hundreds = (int) floor($num / 100);
        $remainder = $num % 100;
        $res = $lookup[$hundreds] . ' Hundred';
        if ($remainder > 0) {
            $res .= ' ' . self::convertChunk($remainder, $lookup);
        }
        return $res;
    }
}
