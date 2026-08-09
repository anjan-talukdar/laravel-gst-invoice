<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;

class EInvoiceService
{
    public function isApplicable(GstInvoice $invoice): bool
    {
        return $invoice->invoice_type === InvoiceType::TAX_INVOICE
            && !empty($invoice->recipient_gstin)
            && !empty($invoice->supplier_gstin);
    }

    public function validatePayload(GstInvoice $invoice): array
    {
        $errors = [];

        if (empty($invoice->supplier_gstin)) {
            $errors[] = 'Supplier GSTIN is required for e-invoicing.';
        }
        if (empty($invoice->recipient_gstin)) {
            $errors[] = 'Recipient GSTIN is required for B2B e-invoicing.';
        }
        if (empty($invoice->supplier_pincode)) {
            $errors[] = 'Supplier pincode is required.';
        }
        if (empty($invoice->recipient_pincode)) {
            $errors[] = 'Recipient pincode is required.';
        }

        return $errors;
    }

    public function generatePayload(GstInvoice $invoice): array
    {
        $items = $invoice->items->map(function ($item, $idx) {
            return [
                'SlNo' => (string)($idx + 1),
                'IsServc' => $item->code_type?->value === 'SAC' ? 'Y' : 'N',
                'HsnCd' => $item->code,
                'Qty' => (float)$item->quantity,
                'Unit' => $item->unit ?: 'NOS',
                'UnitPrice' => (float)$item->unit_price,
                'TotAmt' => (float)($item->quantity * $item->unit_price),
                'Discount' => (float)$item->item_discount,
                'AssVal' => (float)$item->taxable_amount,
                'GstRt' => (float)$item->gst_rate,
                'IgstAmt' => (float)$item->igst_amount,
                'CgstAmt' => (float)$item->cgst_amount,
                'SgstAmt' => (float)$item->sgst_amount,
                'TotItemVal' => (float)$item->total_amount,
            ];
        })->toArray();

        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => 'B2B',
                'RegRev' => $invoice->is_reverse_charge ? 'Y' : 'N',
            ],
            'DocDtls' => [
                'Typ' => match ($invoice->invoice_type) {
                    InvoiceType::CREDIT_NOTE => 'CRN',
                    InvoiceType::DEBIT_NOTE => 'DBN',
                    default => 'INV',
                },
                'No' => $invoice->invoice_number,
                'Dt' => $invoice->invoice_date->format('d/m/Y'),
            ],
            'SellerDtls' => [
                'Gstin' => $invoice->supplier_gstin,
                'LglNm' => $invoice->supplier_name,
                'Addr1' => $invoice->supplier_address,
                'Loc' => $invoice->supplier_city,
                'Pin' => (int)$invoice->supplier_pincode,
                'Stcd' => $invoice->supplier_state_code,
            ],
            'BuyerDtls' => [
                'Gstin' => $invoice->recipient_gstin,
                'LglNm' => $invoice->recipient_name,
                'Pos' => $invoice->pos_state_code,
                'Addr1' => $invoice->recipient_address,
                'Loc' => $invoice->recipient_city,
                'Pin' => (int)$invoice->recipient_pincode,
                'Stcd' => $invoice->recipient_state_code,
            ],
            'ItemList' => $items,
            'ValDtls' => [
                'AssVal' => (float)$invoice->subtotal,
                'CgstVal' => (float)$invoice->cgst_amount,
                'SgstVal' => (float)$invoice->sgst_amount,
                'IgstVal' => (float)$invoice->igst_amount,
                'RndOffAmt' => (float)$invoice->round_off,
                'TotInvVal' => (float)$invoice->total,
            ],
        ];
    }
}
