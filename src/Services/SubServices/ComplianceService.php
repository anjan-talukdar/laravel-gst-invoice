<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;

class ComplianceService
{
    /**
     * Build GSTR-1 summary data array.
     */
    public function getGstr1Data(?string $returnPeriod = null, ?string $financialYear = null): array
    {
        return [
            'b2b' => $this->getGstr1B2BData($returnPeriod),
            'b2c' => $this->getGstr1B2CData($returnPeriod),
            'cdnr' => $this->getGstr1CreditDebitNotes($returnPeriod),
            'advances' => $this->getGstr1AdvanceData($returnPeriod),
        ];
    }

    public function getGstr1B2BData(?string $returnPeriod = null): array
    {
        $query = GstInvoice::where('invoice_type', InvoiceType::TAX_INVOICE->value)
            ->whereNotNull('recipient_gstin')
            ->where('status', '!=', InvoiceStatus::CANCELLED->value);

        $this->applyReturnPeriodFilter($query, $returnPeriod);

        return $query->get()->map(fn(GstInvoice $inv) => [
            'gstin' => $inv->recipient_gstin,
            'recipient_name' => $inv->recipient_name,
            'invoice_number' => $inv->invoice_number,
            'invoice_date' => $inv->invoice_date->format('Y-m-d'),
            'invoice_value' => (float)$inv->total,
            'pos' => $inv->pos_state_code,
            'reverse_charge' => $inv->is_reverse_charge ? 'Y' : 'N',
            'taxable_value' => (float)$inv->subtotal,
            'cgst' => (float)$inv->cgst_amount,
            'sgst' => (float)$inv->sgst_amount,
            'igst' => (float)$inv->igst_amount,
        ])->toArray();
    }

    public function getGstr1B2CData(?string $returnPeriod = null): array
    {
        $query = GstInvoice::where('invoice_type', InvoiceType::TAX_INVOICE->value)
            ->whereNull('recipient_gstin')
            ->where('status', '!=', InvoiceStatus::CANCELLED->value);

        $this->applyReturnPeriodFilter($query, $returnPeriod);

        return $query->get()->map(fn(GstInvoice $inv) => [
            'invoice_number' => $inv->invoice_number,
            'invoice_date' => $inv->invoice_date->format('Y-m-d'),
            'invoice_value' => (float)$inv->total,
            'pos' => $inv->pos_state_code,
            'taxable_value' => (float)$inv->subtotal,
            'cgst' => (float)$inv->cgst_amount,
            'sgst' => (float)$inv->sgst_amount,
            'igst' => (float)$inv->igst_amount,
        ])->toArray();
    }

    public function getGstr1CreditDebitNotes(?string $returnPeriod = null): array
    {
        $query = GstInvoice::whereIn('invoice_type', [InvoiceType::CREDIT_NOTE->value, InvoiceType::DEBIT_NOTE->value])
            ->where('status', '!=', InvoiceStatus::CANCELLED->value);

        $this->applyReturnPeriodFilter($query, $returnPeriod);

        return $query->get()->map(fn(GstInvoice $inv) => [
            'note_type' => $inv->invoice_type->value === InvoiceType::CREDIT_NOTE->value ? 'C' : 'D',
            'note_number' => $inv->invoice_number,
            'note_date' => $inv->invoice_date->format('Y-m-d'),
            'original_invoice_number' => $inv->referenceInvoice?->invoice_number,
            'original_invoice_date' => $inv->referenceInvoice?->invoice_date?->format('Y-m-d'),
            'recipient_gstin' => $inv->recipient_gstin,
            'taxable_value' => (float)$inv->subtotal,
            'cgst' => (float)$inv->cgst_amount,
            'sgst' => (float)$inv->sgst_amount,
            'igst' => (float)$inv->igst_amount,
        ])->toArray();
    }

    public function getGstr1AdvanceData(?string $returnPeriod = null): array
    {
        $query = GstInvoice::where('invoice_type', InvoiceType::RECEIPT_VOUCHER->value)
            ->where('status', '!=', InvoiceStatus::CANCELLED->value);

        $this->applyReturnPeriodFilter($query, $returnPeriod);

        return $query->get()->map(fn(GstInvoice $inv) => [
            'voucher_number' => $inv->invoice_number,
            'voucher_date' => $inv->invoice_date->format('Y-m-d'),
            'recipient_name' => $inv->recipient_name,
            'amount' => (float)$inv->total,
            'cgst' => (float)$inv->cgst_amount,
            'sgst' => (float)$inv->sgst_amount,
            'igst' => (float)$inv->igst_amount,
        ])->toArray();
    }

    public function exportGstr1Json(?string $returnPeriod = null): string
    {
        return json_encode($this->getGstr1Data($returnPeriod), JSON_PRETTY_PRINT);
    }

    public function exportGstr1Csv(?string $returnPeriod = null): string
    {
        $data = $this->getGstr1B2BData($returnPeriod);
        if (empty($data)) {
            return "GSTIN,Recipient Name,Invoice Number,Invoice Date,Invoice Value,POS,Taxable Value,CGST,SGST,IGST\n";
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    protected function applyReturnPeriodFilter($query, ?string $returnPeriod): void
    {
        if (!$returnPeriod) {
            return;
        }

        // Return period format 'YYYY-MM' e.g. '2026-08'
        $parts = explode('-', $returnPeriod);
        if (count($parts) === 2) {
            $query->whereYear('invoice_date', (int)$parts[0])
                ->whereMonth('invoice_date', (int)$parts[1]);
        }
    }
}
