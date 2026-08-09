<?php

namespace AnjanTalukdar\GstInvoice\Services;

use AnjanTalukdar\GstInvoice\Contracts\InvoiceNumberGeneratorInterface;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Models\InvoiceNumberSequence;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

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

        $invoiceTypeInput = $options['invoice_type'] ?? InvoiceType::TAX_INVOICE;
        $invoiceType = $invoiceTypeInput instanceof InvoiceType
            ? $invoiceTypeInput
            : (InvoiceType::tryFrom((string)$invoiceTypeInput) ?? InvoiceType::TAX_INVOICE);

        $prefix = $options['prefix'] ?? $invoiceType->defaultPrefix();
        $padding = (int)($options['serial_padding'] ?? config('gst-invoice.serial_padding', 5));

        return DB::transaction(function () use ($invoiceType, $fyCode, $prefix, $padding) {
            $sequence = InvoiceNumberSequence::where('invoice_type', $invoiceType->value)
                ->where('financial_year', $fyCode)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                $sequence = InvoiceNumberSequence::create([
                    'invoice_type' => $invoiceType->value,
                    'financial_year' => $fyCode,
                    'prefix' => $prefix,
                    'current_sequence' => 0,
                ]);

                // Re-lock newly created sequence row
                $sequence = InvoiceNumberSequence::where('id', $sequence->id)
                    ->lockForUpdate()
                    ->first();
            }

            // Update prefix if custom prefix supplied in options
            if ($sequence->prefix !== $prefix) {
                $sequence->prefix = $prefix;
            }

            $sequence->current_sequence += 1;
            $sequence->save();

            $serialStr = str_pad((string)$sequence->current_sequence, $padding, '0', STR_PAD_LEFT);

            return "{$prefix}/{$fyCode}/{$serialStr}";
        });
    }
}
