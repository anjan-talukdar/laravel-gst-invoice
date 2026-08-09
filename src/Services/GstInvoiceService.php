<?php

namespace AnjanTalukdar\GstInvoice\Services;

use AnjanTalukdar\GstInvoice\Contracts\GstRecipientInterface;
use AnjanTalukdar\GstInvoice\Contracts\InvoiceNumberGeneratorInterface;
use AnjanTalukdar\GstInvoice\Contracts\TaxCalculatorInterface;
use AnjanTalukdar\GstInvoice\Data\BillingSummaryData;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Data\PaymentInput;
use AnjanTalukdar\GstInvoice\Data\RecipientInput;
use AnjanTalukdar\GstInvoice\Data\SupplierInput;
use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\PaymentStatus;
use AnjanTalukdar\GstInvoice\Events\InvoiceCreated;
use AnjanTalukdar\GstInvoice\Events\InvoiceCreating;
use AnjanTalukdar\GstInvoice\Events\InvoicePaid;
use AnjanTalukdar\GstInvoice\Events\InvoicePartiallyPaid;
use AnjanTalukdar\GstInvoice\Events\InvoicePaymentStatusChanged;
use AnjanTalukdar\GstInvoice\Events\InvoicePaymentStatusChanging;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use AnjanTalukdar\GstInvoice\Models\GstInvoiceItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GstInvoiceService
{
    public function __construct(
        protected TaxCalculatorInterface $taxCalculator,
        protected InvoiceNumberGeneratorInterface $numberGenerator,
        protected GstInvoiceValidator $validator
    ) {}

    /**
     * Calculate billing summary for checkout / quotation preview without saving.
     *
     * @param InvoiceItemInput[] $items
     * @param InvoiceOptions|null $options
     */
    public function calculateSummary(array $items, ?InvoiceOptions $options = null): BillingSummaryData
    {
        return $this->taxCalculator->calculate($items, $options);
    }

    /**
     * Create an immutable GST Invoice with normalized items and JSON snapshot.
     *
     * @param RecipientInput|GstRecipientInterface|Model $recipient
     * @param InvoiceItemInput[] $items
     * @param InvoiceOptions|null $options
     */
    public function createInvoice(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $options = $options ?? new InvoiceOptions();

        $this->validator->validateInvoiceInput($items, $options);

        event(new InvoiceCreating(['items' => $items, 'recipient' => $recipient], $options->toArray()));

        $recipientSnapshot = $this->extractRecipientSnapshot($recipient, $options);
        $supplierSnapshot = $this->extractSupplierSnapshot($options);

        // Populate supplier & recipient state codes for POS calculation if omitted
        if (empty($options->supplierStateCode)) {
            $options->supplierStateCode($supplierSnapshot['state_code']);
        }
        if (empty($options->posStateCode)) {
            $options->posStateCode($recipientSnapshot['shipping_state_code'] ?: $recipientSnapshot['state_code']);
        }
        if (empty($options->posStateName)) {
            $options->posStateName($recipientSnapshot['shipping_state_name'] ?: $recipientSnapshot['state_name']);
        }

        $summaryData = $this->calculateSummary($items, $options);

        return DB::transaction(function () use ($recipient, $supplierSnapshot, $recipientSnapshot, $summaryData, $options) {
            $invoiceDate = $options->invoiceDate ? Carbon::parse($options->invoiceDate) : now();
            $dueDays = $options->dueDays ?? (int)config('gst-invoice.default_due_days', 7);
            $dueDate = $options->dueDate ? Carbon::parse($options->dueDate) : $invoiceDate->copy()->addDays($dueDays);

            $invoiceNumber = $options->invoiceNumber ?? $this->numberGenerator->generate($invoiceDate, $options->toArray());
            $invoicableModel = $options->invoicable;

            $invoice = GstInvoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'payment_terms' => $options->paymentTerms ?? config('gst-invoice.default_payment_terms', 'due_on_receipt'),
                'payment_mode' => $options->paymentMode ?? config('gst-invoice.default_payment_mode', 'bank_transfer'),

                'invoicable_type' => $invoicableModel instanceof Model ? get_class($invoicableModel) : null,
                'invoicable_id' => $invoicableModel instanceof Model ? $invoicableModel->getKey() : null,

                'recipient_type' => $recipient instanceof Model ? get_class($recipient) : null,
                'recipient_id' => $recipient instanceof Model ? $recipient->getKey() : null,

                'supplier_name' => $supplierSnapshot['name'],
                'supplier_gstin' => $supplierSnapshot['gstin'],
                'supplier_pan' => $supplierSnapshot['pan'],
                'supplier_address' => $supplierSnapshot['address'],
                'supplier_city' => $supplierSnapshot['city'],
                'supplier_state_name' => $supplierSnapshot['state_name'],
                'supplier_state_code' => $supplierSnapshot['state_code'],
                'supplier_pincode' => $supplierSnapshot['pincode'],
                'supplier_email' => $supplierSnapshot['email'],
                'supplier_phone' => $supplierSnapshot['phone'],

                'recipient_name' => $recipientSnapshot['name'],
                'recipient_email' => $recipientSnapshot['email'],
                'recipient_phone' => $recipientSnapshot['phone'],
                'recipient_gstin' => $recipientSnapshot['gstin'],
                'recipient_pan' => $recipientSnapshot['pan'],
                'recipient_address' => $recipientSnapshot['address'],
                'recipient_city' => $recipientSnapshot['city'],
                'recipient_state_name' => $recipientSnapshot['state_name'],
                'recipient_state_code' => $recipientSnapshot['state_code'],
                'recipient_pincode' => $recipientSnapshot['pincode'],

                'pos_state_name' => $summaryData->posStateName,
                'pos_state_code' => $summaryData->posStateCode,
                'is_interstate' => $summaryData->isInterstate,
                'is_reverse_charge' => $summaryData->isReverseCharge,
                'discount_mode' => $summaryData->discountMode ?? 'bill',

                'gross_taxable' => $summaryData->summary->grossTaxable,
                'discount' => $summaryData->summary->discount,
                'subtotal' => $summaryData->summary->subtotal,
                'cgst_amount' => $summaryData->summary->cgstAmount,
                'sgst_amount' => $summaryData->summary->sgstAmount,
                'igst_amount' => $summaryData->summary->igstAmount,
                'gst_amount' => $summaryData->summary->gstAmount,
                'round_off' => $summaryData->summary->roundOff,
                'total' => $summaryData->summary->total,
                'currency' => $options->currency ?? config('gst-invoice.currency_code', 'INR'),
                'paid_amount' => 0.00,
                'due_amount' => $summaryData->summary->total,

                'payment_status' => PaymentStatus::UNPAID->value,
                'status' => InvoiceStatus::ACTIVE->value,
                'remark' => $options->remark,
                'created_by' => $options->createdBy ?? Auth::id(),
            ]);

            // Save normalized items
            foreach ($summaryData->items as $itemData) {
                GstInvoiceItem::create([
                    'gst_invoice_id' => $invoice->id,
                    'description' => $itemData->description,
                    'code_type' => $itemData->codeType,
                    'code' => $itemData->code,
                    'tax_category' => $itemData->taxCategory,
                    'quantity' => $itemData->quantity,
                    'unit' => $itemData->unit,
                    'unit_price' => $itemData->unitPrice,
                    'item_discount' => $itemData->itemDiscount,
                    'bill_discount' => $itemData->billDiscount,
                    'taxable_amount' => $itemData->taxableAmount,
                    'gst_rate' => $itemData->gstRate,
                    'cgst_amount' => $itemData->cgstAmount,
                    'sgst_amount' => $itemData->sgstAmount,
                    'igst_amount' => $itemData->igstAmount,
                    'total_amount' => $itemData->totalAmount,
                    'sort_order' => $itemData->sortOrder,
                    'meta_data' => $itemData->metaData,
                ]);
            }

            // Build full DTO snapshot with schema_version: "1.0"
            $invoice->refresh();
            $structuredDTO = $invoice->toStructuredData();
            $invoice->updateQuietly(['billing_details' => $structuredDTO->toArray()]);

            event(new InvoiceCreated($invoice));

            return $invoice;
        });
    }

    /**
     * Mark an invoice as paid or partially paid.
     */
    public function markAsPaid(GstInvoice $invoice, ?PaymentInput $paymentData = null): GstInvoice
    {
        $paymentData = $paymentData ?? new PaymentInput();

        $amount = $paymentData->amount !== null ? $paymentData->amount : (float)$invoice->total;
        $paidAt = $paymentData->paidAt ? Carbon::parse($paymentData->paidAt) : now();

        $previousStatus = $invoice->payment_status->value;

        if ($amount >= (float)$invoice->total) {
            $newStatus = PaymentStatus::PAID->value;
            $newPaid = (float)$invoice->total;
            $newDue = 0.00;
        } else {
            $newStatus = PaymentStatus::PARTIAL->value;
            $newPaid = round($invoice->paid_amount + $amount, 2);
            $newDue = max(0.00, round((float)$invoice->total - $newPaid, 2));
        }

        event(new InvoicePaymentStatusChanging($invoice, $newStatus, $amount));

        $invoice->updateQuietly([
            'payment_status' => $newStatus,
            'paid_amount' => $newPaid,
            'due_amount' => $newDue,
            'paid_at' => $newPaid > 0 ? $paidAt : null,
        ]);

        $invoice->refresh();

        // Refresh snapshot summary
        $structured = $invoice->toStructuredData();
        $invoice->updateQuietly(['billing_details' => $structured->toArray()]);

        event(new InvoicePaymentStatusChanged($invoice, $previousStatus, $newStatus));

        if ($newStatus === PaymentStatus::PAID->value) {
            event(new InvoicePaid($invoice, $paymentData->toArray()));
        } else {
            event(new InvoicePartiallyPaid($invoice, $newPaid, $newDue));
        }

        return $invoice;
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(GstInvoice $invoice, ?string $reason = null, mixed $cancelledBy = null): bool
    {
        return $invoice->cancelInvoice($reason, $cancelledBy);
    }

    /**
     * Extract recipient snapshot array.
     */
    protected function extractRecipientSnapshot(mixed $recipient, InvoiceOptions $options): array
    {
        if ($recipient instanceof RecipientInput) {
            $gstin = $recipient->gstin;
            return [
                'name' => $recipient->name ?: 'Customer',
                'trade_name' => $recipient->tradeName,
                'email' => $recipient->email,
                'phone' => $recipient->phone,
                'gstin' => $gstin,
                'pan' => $recipient->pan ?: $this->validator->extractPanFromGstin($gstin),
                'address' => $recipient->address,
                'city' => $recipient->city,
                'state_name' => $recipient->stateName,
                'state_code' => $recipient->stateCode,
                'pincode' => $recipient->pincode,
                'shipping_address' => $recipient->shippingAddress,
                'shipping_city' => $recipient->shippingCity,
                'shipping_state_name' => $recipient->shippingStateName,
                'shipping_state_code' => $recipient->shippingStateCode,
                'shipping_pincode' => $recipient->shippingPincode,
            ];
        }

        if ($options->recipient instanceof RecipientInput) {
            return $this->extractRecipientSnapshot($options->recipient, $options);
        }

        if ($recipient instanceof GstRecipientInterface) {
            $gstin = $recipient->getGstBillingGstin();
            return [
                'name' => $recipient->getGstBillingName(),
                'trade_name' => null,
                'email' => $recipient->getGstBillingEmail(),
                'phone' => $recipient->getGstBillingPhone(),
                'gstin' => $gstin,
                'pan' => $recipient->getGstBillingPan() ?: $this->validator->extractPanFromGstin($gstin),
                'address' => $recipient->getGstBillingAddress(),
                'city' => $recipient->getGstBillingCity(),
                'state_name' => $recipient->getGstBillingStateName(),
                'state_code' => $recipient->getGstBillingStateCode(),
                'pincode' => $recipient->getGstBillingPincode(),
                'shipping_address' => null,
                'shipping_city' => null,
                'shipping_state_name' => null,
                'shipping_state_code' => null,
                'shipping_pincode' => null,
            ];
        }

        if ($recipient instanceof Model) {
            $gstin = $recipient->gstin ?? ($recipient->billing_gstin ?? null);
            return [
                'name' => $recipient->billing_name ?? ($recipient->business_name ?? ($recipient->name ?? 'Customer')),
                'trade_name' => $recipient->trade_name ?? null,
                'email' => $recipient->billing_email ?? ($recipient->email ?? null),
                'phone' => $recipient->billing_phone ?? ($recipient->phone ?? null),
                'gstin' => $gstin,
                'pan' => $this->validator->extractPanFromGstin($gstin),
                'address' => $recipient->billing_address ?? ($recipient->address ?? null),
                'city' => $recipient->billing_city ?? ($recipient->city ?? null),
                'state_name' => $recipient->billing_state_name ?? null,
                'state_code' => $recipient->billing_state_code ?? null,
                'pincode' => $recipient->billing_pincode ?? ($recipient->pincode ?? null),
                'shipping_address' => $recipient->shipping_address ?? null,
                'shipping_city' => $recipient->shipping_city ?? null,
                'shipping_state_name' => $recipient->shipping_state_name ?? null,
                'shipping_state_code' => $recipient->shipping_state_code ?? null,
                'shipping_pincode' => $recipient->shipping_pincode ?? null,
            ];
        }

        return [
            'name' => 'Customer',
            'trade_name' => null,
            'email' => null,
            'phone' => null,
            'gstin' => null,
            'pan' => null,
            'address' => null,
            'city' => null,
            'state_name' => null,
            'state_code' => null,
            'pincode' => null,
            'shipping_address' => null,
            'shipping_city' => null,
            'shipping_state_name' => null,
            'shipping_state_code' => null,
            'shipping_pincode' => null,
        ];
    }

    /**
     * Extract supplier snapshot from config or options.
     */
    protected function extractSupplierSnapshot(InvoiceOptions $options): array
    {
        $config = config('gst-invoice.supplier', []);
        $opt = $options->supplier;

        $gstin = $opt?->gstin ?: ($config['gstin'] ?? null);

        return [
            'name' => $opt?->name ?: ($config['name'] ?? 'Supplier'),
            'trade_name' => $opt?->tradeName ?: ($config['trade_name'] ?? null),
            'gstin' => $gstin,
            'pan' => $opt?->pan ?: ($config['pan'] ?? $this->validator->extractPanFromGstin($gstin)),
            'address' => $opt?->address ?: ($config['address'] ?? null),
            'city' => $opt?->city ?: ($config['city'] ?? null),
            'state_name' => $opt?->stateName ?: ($config['state'] ?? null),
            'state_code' => $opt?->stateCode ?: ($config['state_code'] ?? null),
            'pincode' => $opt?->pincode ?: ($config['pincode'] ?? null),
            'email' => $opt?->email ?: ($config['email'] ?? null),
            'phone' => $opt?->phone ?: ($config['phone'] ?? null),
            'bank_details' => $opt?->bankDetails ? $opt->bankDetails->toArray() : ($config['bank_details'] ?? []),
        ];
    }
}
