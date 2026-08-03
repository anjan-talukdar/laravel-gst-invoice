<?php

namespace AnjanTalukdar\GstInvoice\Services;

use AnjanTalukdar\GstInvoice\Contracts\InvoiceNumberGeneratorInterface;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use Carbon\Carbon;
use DateTimeInterface;

class SequentialFyInvoiceNumberGenerator implements InvoiceNumberGeneratorInterface
{
    public function generate(DateTimeInterface $date, array $options = []): string
    {
        $carbonDate = Carbon::instance($date);
        $year = $carbonDate->year;
        $month = $carbonDate->month;

        if ($month >= 4) {
            $startYear = $year;
            $endYear = $year + 1;
        } else {
            $startYear = $year - 1;
            $endYear = $year;
        }

        $fyCode = substr((string)$startYear, -2) . '-' . substr((string)$endYear, -2);

        $prefixSetting = $options['prefix'] ?? config('gst-invoice.prefix', 'INV');
        $padding = (int)($options['serial_padding'] ?? config('gst-invoice.serial_padding', 5));

        $fullPrefix = "{$prefixSetting}/{$fyCode}/";

        $lastInvoice = GstInvoice::where('invoice_number', 'like', "{$fullPrefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSerial = 1;
        if ($lastInvoice) {
            $parts = explode('/', $lastInvoice->invoice_number);
            $lastSerial = (int)end($parts);
            $nextSerial = $lastSerial + 1;
        }

        return $fullPrefix . str_pad((string)$nextSerial, $padding, '0', STR_PAD_LEFT);
    }
}
