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
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Enums\PaymentStatus;
use AnjanTalukdar\GstInvoice\Events\InvoiceCreated;
use AnjanTalukdar\GstInvoice\Events\InvoiceCreating;
use AnjanTalukdar\GstInvoice\Events\InvoicePaid;
use AnjanTalukdar\GstInvoice\Events\InvoicePartiallyPaid;
use AnjanTalukdar\GstInvoice\Events\InvoicePaymentStatusChanged;
use AnjanTalukdar\GstInvoice\Events\InvoicePaymentStatusChanging;
use AnjanTalukdar\GstInvoice\Events\InvoiceUpdated;
use AnjanTalukdar\GstInvoice\Exceptions\InvalidGstInvoiceException;
use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use AnjanTalukdar\GstInvoice\Models\GstInvoiceItem;
use AnjanTalukdar\GstInvoice\Services\SubServices\BillOfSupplyService;
use AnjanTalukdar\GstInvoice\Services\SubServices\ComplianceService;
use AnjanTalukdar\GstInvoice\Services\SubServices\CreditNoteService;
use AnjanTalukdar\GstInvoice\Services\SubServices\DebitNoteService;
use AnjanTalukdar\GstInvoice\Services\SubServices\EInvoiceService;
use AnjanTalukdar\GstInvoice\Services\SubServices\QuotationService;
use AnjanTalukdar\GstInvoice\Services\SubServices\ReceiptVoucherService;
use AnjanTalukdar\GstInvoice\Services\SubServices\RenderingService;
use AnjanTalukdar\GstInvoice\Services\SubServices\SimpleReceiptService;
use AnjanTalukdar\GstInvoice\Services\SubServices\TaxInvoiceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GstInvoiceService
{
    protected ?TaxInvoiceService $taxInvoiceService = null;
    protected ?QuotationService $quotationService = null;
    protected ?CreditNoteService $creditNoteService = null;
    protected ?DebitNoteService $debitNoteService = null;
    protected ?ReceiptVoucherService $receiptVoucherService = null;
    protected ?BillOfSupplyService $billOfSupplyService = null;
    protected ?SimpleReceiptService $simpleReceiptService = null;
    protected ?ComplianceService $complianceService = null;
    protected ?EInvoiceService $eInvoiceService = null;
    protected ?RenderingService $renderingService = null;

    public function __construct(
        protected TaxCalculatorInterface $taxCalculator,
        protected InvoiceNumberGeneratorInterface $numberGenerator,
        protected GstInvoiceValidator $validator
    ) {}

    // --- Fluent Sub-Service Accessors ---

    public function taxInvoice(): TaxInvoiceService
    {
        return $this->taxInvoiceService ??= new TaxInvoiceService($this);
    }

    public function quotations(): QuotationService
    {
        return $this->quotationService ??= new QuotationService($this);
    }

    public function creditNotes(): CreditNoteService
    {
        return $this->creditNoteService ??= new CreditNoteService($this);
    }

    public function debitNotes(): DebitNoteService
    {
        return $this->debitNoteService ??= new DebitNoteService($this);
    }

    public function receiptVouchers(): ReceiptVoucherService
    {
        return $this->receiptVoucherService ??= new ReceiptVoucherService($this);
    }

    public function billsOfSupply(): BillOfSupplyService
    {
        return $this->billOfSupplyService ??= new BillOfSupplyService($this);
    }

    public function simpleReceipts(): SimpleReceiptService
    {
        return $this->simpleReceiptService ??= new SimpleReceiptService($this);
    }

    public function reports(): ComplianceService
    {
        return $this->complianceService ??= new ComplianceService();
    }

    public function eInvoice(): EInvoiceService
    {
        return $this->eInvoiceService ??= new EInvoiceService();
    }

    public function rendering(): RenderingService
    {
        return $this->renderingService ??= new RenderingService();
    }

    // --- Core Billing & Document Engine Methods ---

    /**
     * Calculate billing summary for checkout / preview without saving.
     *
     * @param InvoiceItemInput[] $items
     * @param InvoiceOptions|null $options
     */
    public function calculateSummary(array $items, ?InvoiceOptions $options = null): BillingSummaryData
    {
        return $this->taxCalculator->calculate($items, $options);
    }

    /**
     * Create a GST Invoice document (Tax Invoice, Quotation, Credit Note, Debit Note, Receipt Voucher, Bill of Supply, Simple Receipt).
     *
     * @param RecipientInput|GstRecipientInterface|Model $recipient
     * @param InvoiceItemInput[] $items
     * @param InvoiceOptions|null $options
     */
    public function createInvoice(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $options = $options ?? new InvoiceOptions();

        $invoiceType = $options->invoiceType
            ? (InvoiceType::tryFrom($options->invoiceType) ?? InvoiceType::TAX_INVOICE)
            : InvoiceType::TAX_INVOICE;

        $statusValue = $options->status ?? (
            $invoiceType === InvoiceType::QUOTATION ? InvoiceStatus::SENT->value : InvoiceStatus::ISSUED->value
        );

        $this->validator->validateStatusTransition($invoiceType, $statusValue);
        $this->validator->validateInvoiceInput($items, $options);

        event(new InvoiceCreating(['items' => $items, 'recipient' => $recipient], $options->toArray()));

        $recipientSnapshot = $this->extractRecipientSnapshot($recipient, $options);
        $supplierSnapshot = $this->extractSupplierSnapshot($options);

        if (empty($options->supplierStateCode)) {
            $options->supplierStateCode($supplierSnapshot['state_code']);
        }
        if (empty($options->posStateCode)) {
            $options->posStateCode($recipientSnapshot['shipping_state_code'] ?: ($recipientSnapshot['state_code'] ?: $supplierSnapshot['state_code']));
        }
        if (empty($options->posStateName)) {
            $options->posStateName($recipientSnapshot['shipping_state_name'] ?: ($recipientSnapshot['state_name'] ?: $supplierSnapshot['state_name']));
        }

        $summaryData = $this->calculateSummary($items, $options);

        $posStateCode = $summaryData->posStateCode ?: ($options->posStateCode ?: $supplierSnapshot['state_code']);
        $posStateName = $summaryData->posStateName ?: ($options->posStateName ?: $supplierSnapshot['state_name']);

        return DB::transaction(function () use ($recipient, $supplierSnapshot, $recipientSnapshot, $summaryData, $options, $items, $invoiceType, $statusValue, $posStateCode, $posStateName) {
            $invoiceDate = $options->invoiceDate ? Carbon::parse($options->invoiceDate) : now();
            $dueDays = $options->dueDays ?? (int)config('gst-invoice.default_due_days', 7);
            $dueDate = $options->dueDate ? Carbon::parse($options->dueDate) : $invoiceDate->copy()->addDays($dueDays);

            $generatorOptions = array_merge($options->toArray(), ['invoice_type' => $invoiceType->value]);
            $invoiceNumber = $options->invoiceNumber ?? $this->numberGenerator->generate($invoiceDate, $generatorOptions);
            $invoicableModel = $options->invoicable;

            $paymentStatus = match ($invoiceType) {
                InvoiceType::TAX_INVOICE => $options->paymentStatus ?? PaymentStatus::UNPAID->value,
                default => null,
            };

            $invoice = GstInvoice::create([
                'invoice_type' => $invoiceType->value,
                'reference_invoice_id' => $options->referenceInvoiceId,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'payment_terms' => $options->paymentTerms ?? config('gst-invoice.default_payment_terms', 'due_on_receipt'),

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

                'pos_state_name' => $posStateName,
                'pos_state_code' => $posStateCode,
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

                'payment_status' => $paymentStatus,
                'status' => $statusValue,
                'remark' => $options->remark,
                'created_by' => $options->createdBy ?? Auth::id(),
            ]);

            foreach ($summaryData->items as $index => $itemData) {
                $inputItem = $items[$index] ?? null;
                $refItemId = null;
                if ($inputItem instanceof InvoiceItemInput) {
                    $refItemId = $inputItem->referenceInvoiceItemId;
                } elseif (is_array($inputItem)) {
                    $refItemId = $inputItem['reference_invoice_item_id'] ?? ($inputItem['referenceInvoiceItemId'] ?? null);
                }

                GstInvoiceItem::create([
                    'gst_invoice_id' => $invoice->id,
                    'reference_invoice_item_id' => $refItemId,
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

            $invoice->refresh();
            $structuredDTO = $invoice->toStructuredData();
            $invoice->updateQuietly(['billing_details' => $structuredDTO->toArray()]);

            event(new InvoiceCreated($invoice));

            return $invoice;
        });
    }

    /**
     * Create a Quotation.
     */
    public function createQuotation(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $options = $options ?? new InvoiceOptions();
        $options->invoiceType(InvoiceType::QUOTATION);

        return $this->createInvoice($recipient, $items, $options);
    }

    /**
     * Create a Tax Invoice.
     */
    public function createTaxInvoice(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $options = $options ?? new InvoiceOptions();
        $options->invoiceType(InvoiceType::TAX_INVOICE);

        return $this->createInvoice($recipient, $items, $options);
    }

    /**
     * Create a Line-Item Based Credit Note.
     */
    public function createCreditNote(GstInvoice $originalInvoice, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $this->validator->validateCreditNote($originalInvoice, $items);

        $options = $options ?? new InvoiceOptions();
        $options->invoiceType(InvoiceType::CREDIT_NOTE)
            ->referenceInvoiceId($originalInvoice->id);

        $recipient = $originalInvoice->recipient ?: $this->extractRecipientInputFromInvoice($originalInvoice);

        return $this->createInvoice($recipient, $items, $options);
    }

    /**
     * Create a Debit Note.
     */
    public function createDebitNote(GstInvoice $originalInvoice, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        if ($originalInvoice->invoice_type !== InvoiceType::TAX_INVOICE) {
            throw new InvalidGstInvoiceException('Debit Notes must reference an original Tax Invoice.');
        }

        $options = $options ?? new InvoiceOptions();
        $options->invoiceType(InvoiceType::DEBIT_NOTE)
            ->referenceInvoiceId($originalInvoice->id);

        $recipient = $originalInvoice->recipient ?: $this->extractRecipientInputFromInvoice($originalInvoice);

        return $this->createInvoice($recipient, $items, $options);
    }

    /**
     * Create a Receipt Voucher.
     */
    public function createReceiptVoucher(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $options = $options ?? new InvoiceOptions();
        $options->invoiceType(InvoiceType::RECEIPT_VOUCHER);

        return $this->createInvoice($recipient, $items, $options);
    }

    /**
     * Create a Bill of Supply (for exempt/composition supplies).
     */
    public function createBillOfSupply(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $options = $options ?? new InvoiceOptions();
        $options->invoiceType(InvoiceType::BILL_OF_SUPPLY);

        return $this->createInvoice($recipient, $items, $options);
    }

    /**
     * Create a Simple Receipt document.
     */
    public function createSimpleReceipt(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
    {
        $options = $options ?? new InvoiceOptions();
        $options->invoiceType(InvoiceType::SIMPLE_RECEIPT);

        return $this->createInvoice($recipient, $items, $options);
    }

    /**
     * Convert an Accepted Quotation into a new Tax Invoice.
     */
    public function convertQuotationToTaxInvoice(GstInvoice $quotation, ?array $items = null, ?InvoiceOptions $options = null): GstInvoice
    {
        if ($quotation->invoice_type !== InvoiceType::QUOTATION) {
            throw new InvalidGstInvoiceException("Only documents of type 'quotation' can be converted to a Tax Invoice.");
        }

        if ($quotation->status !== InvoiceStatus::ACCEPTED) {
            throw new InvalidGstInvoiceException("Only accepted quotations (status 'accepted') can be converted to a Tax Invoice. Current status: '{$quotation->status->value}'");
        }

        return DB::transaction(function () use ($quotation, $items, $options) {
            $conversionItems = $items ?? $quotation->items->map(fn(GstInvoiceItem $item) => InvoiceItemInput::fromArray([
                'description' => $item->description,
                'unit_price' => (float)$item->unit_price,
                'quantity' => (float)$item->quantity,
                'unit' => $item->unit,
                'code_type' => $item->code_type?->value ?? 'SAC',
                'code' => $item->code,
                'tax_category' => $item->tax_category?->value ?? 'taxable',
                'gst_rate' => (float)$item->gst_rate,
                'discount' => (float)$item->item_discount,
                'sort_order' => $item->sort_order,
                'meta_data' => $item->meta_data,
            ]))->toArray();

            $options = $options ?? new InvoiceOptions();
            $options->invoiceType(InvoiceType::TAX_INVOICE)
                ->referenceInvoiceId($quotation->id);

            $recipient = $quotation->recipient ?: $this->extractRecipientInputFromInvoice($quotation);

            return $this->createInvoice($recipient, $conversionItems, $options);
        });
    }

    /**
     * Create a Revised Quotation referencing a previous Quotation.
     */
    public function createRevisedQuotation(GstInvoice $quotation, ?array $items = null, ?InvoiceOptions $options = null): GstInvoice
    {
        if ($quotation->invoice_type !== InvoiceType::QUOTATION) {
            throw new InvalidGstInvoiceException("Can only create a revised quotation from an existing quotation.");
        }

        $revisedItems = $items ?? $quotation->items->map(fn(GstInvoiceItem $item) => InvoiceItemInput::fromArray([
            'description' => $item->description,
            'unit_price' => (float)$item->unit_price,
            'quantity' => (float)$item->quantity,
            'unit' => $item->unit,
            'code_type' => $item->code_type?->value ?? 'SAC',
            'code' => $item->code,
            'tax_category' => $item->tax_category?->value ?? 'taxable',
            'gst_rate' => (float)$item->gst_rate,
            'discount' => (float)$item->item_discount,
            'sort_order' => $item->sort_order,
            'meta_data' => $item->meta_data,
        ]))->toArray();

        $options = $options ?? new InvoiceOptions();
        $options->invoiceType(InvoiceType::QUOTATION)
            ->referenceInvoiceId($quotation->id);

        $recipient = $quotation->recipient ?: $this->extractRecipientInputFromInvoice($quotation);

        return DB::transaction(function () use ($quotation, $revisedItems, $options, $recipient) {
            $revisedQuotation = $this->createInvoice($recipient, $revisedItems, $options);
            $quotation->cancelInvoice("Superseded by revised quotation #{$revisedQuotation->invoice_number}");

            return $revisedQuotation;
        });
    }

    /**
     * Issue a document (transitions draft status to issued).
     */
    public function issueDocument(GstInvoice $invoice): GstInvoice
    {
        if ($invoice->status === InvoiceStatus::ISSUED) {
            return $invoice;
        }

        $this->validator->validateStatusTransition($invoice->invoice_type, InvoiceStatus::ISSUED->value);
        $invoice->update(['status' => InvoiceStatus::ISSUED->value]);
        event(new InvoiceUpdated($invoice));

        return $invoice;
    }

    public function validateDocumentBeforeIssue(GstInvoice $invoice): bool
    {
        return !empty($invoice->invoice_number)
            && !empty($invoice->supplier_name)
            && !empty($invoice->recipient_name)
            && $invoice->items->count() > 0;
    }

    public function recalculateDocument(GstInvoice $invoice): GstInvoice
    {
        return GstInvoice::withoutImmutability(function () use ($invoice) {
            $itemsInput = $invoice->items->map(fn(GstInvoiceItem $item) => InvoiceItemInput::fromArray([
                'description' => $item->description,
                'unit_price' => (float)$item->unit_price,
                'quantity' => (float)$item->quantity,
                'unit' => $item->unit,
                'code_type' => $item->code_type?->value ?? 'SAC',
                'code' => $item->code,
                'tax_category' => $item->tax_category?->value ?? 'taxable',
                'gst_rate' => (float)$item->gst_rate,
                'discount' => (float)$item->item_discount,
                'sort_order' => $item->sort_order,
                'meta_data' => $item->meta_data,
            ]))->toArray();

            $options = InvoiceOptions::make()
                ->supplierStateCode($invoice->supplier_state_code)
                ->posStateCode($invoice->pos_state_code)
                ->posStateName($invoice->pos_state_name)
                ->isInterstate((bool)$invoice->is_interstate)
                ->isReverseCharge((bool)$invoice->is_reverse_charge)
                ->discount((float)$invoice->discount);

            $summaryData = $this->calculateSummary($itemsInput, $options);

            $invoice->update([
                'gross_taxable' => $summaryData->summary->grossTaxable,
                'discount' => $summaryData->summary->discount,
                'subtotal' => $summaryData->summary->subtotal,
                'cgst_amount' => $summaryData->summary->cgstAmount,
                'sgst_amount' => $summaryData->summary->sgstAmount,
                'igst_amount' => $summaryData->summary->igstAmount,
                'gst_amount' => $summaryData->summary->gstAmount,
                'round_off' => $summaryData->summary->roundOff,
                'total' => $summaryData->summary->total,
                'due_amount' => max(0.00, round($summaryData->summary->total - (float)$invoice->paid_amount, 2)),
            ]);

            $invoice->refresh();
            $structured = $invoice->toStructuredData();
            $invoice->updateQuietly(['billing_details' => $structured->toArray()]);

            return $invoice;
        });
    }

    /**
     * Force update an existing GST Invoice document of any type.
     *
     * @param GstInvoice $invoice
     * @param mixed $recipient
     * @param array|null $items
     * @param InvoiceOptions|null $options
     * @param array $additionalAttributes
     * @return GstInvoice
     */
    public function forceUpdateInvoice(
        GstInvoice $invoice,
        mixed $recipient = null,
        ?array $items = null,
        ?InvoiceOptions $options = null,
        array $additionalAttributes = []
    ): GstInvoice {
        return DB::transaction(function () use ($invoice, $recipient, $items, $options, $additionalAttributes) {
            return GstInvoice::withoutImmutability(function () use ($invoice, $recipient, $items, $options, $additionalAttributes) {
                $options = $options ?? new InvoiceOptions();

                $updateData = [];

                if ($recipient !== null) {
                    $recipientSnapshot = $this->extractRecipientSnapshot($recipient, $options);
                    $updateData['recipient_type'] = $recipient instanceof Model ? get_class($recipient) : null;
                    $updateData['recipient_id'] = $recipient instanceof Model ? $recipient->getKey() : null;
                    $updateData['recipient_name'] = $recipientSnapshot['name'];
                    $updateData['recipient_email'] = $recipientSnapshot['email'];
                    $updateData['recipient_phone'] = $recipientSnapshot['phone'];
                    $updateData['recipient_gstin'] = $recipientSnapshot['gstin'];
                    $updateData['recipient_pan'] = $recipientSnapshot['pan'];
                    $updateData['recipient_address'] = $recipientSnapshot['address'];
                    $updateData['recipient_city'] = $recipientSnapshot['city'];
                    $updateData['recipient_state_name'] = $recipientSnapshot['state_name'];
                    $updateData['recipient_state_code'] = $recipientSnapshot['state_code'];
                    $updateData['recipient_pincode'] = $recipientSnapshot['pincode'];
                }

                if ($options->supplier !== null || !empty(config('gst-invoice.supplier'))) {
                    $supplierSnapshot = $this->extractSupplierSnapshot($options);
                    $updateData['supplier_name'] = $supplierSnapshot['name'];
                    $updateData['supplier_gstin'] = $supplierSnapshot['gstin'];
                    $updateData['supplier_pan'] = $supplierSnapshot['pan'];
                    $updateData['supplier_address'] = $supplierSnapshot['address'];
                    $updateData['supplier_city'] = $supplierSnapshot['city'];
                    $updateData['supplier_state_name'] = $supplierSnapshot['state_name'];
                    $updateData['supplier_state_code'] = $supplierSnapshot['state_code'];
                    $updateData['supplier_pincode'] = $supplierSnapshot['pincode'];
                    $updateData['supplier_email'] = $supplierSnapshot['email'];
                    $updateData['supplier_phone'] = $supplierSnapshot['phone'];
                }

                if ($items !== null) {
                    $this->validator->validateInvoiceInput($items, $options);

                    // Re-calculate summary
                    $summaryData = $this->calculateSummary($items, $options);

                    // Sync line items
                    $invoice->items()->delete();

                    foreach ($summaryData->items as $index => $itemData) {
                        $inputItem = $items[$index] ?? null;
                        $refItemId = null;
                        if ($inputItem instanceof InvoiceItemInput) {
                            $refItemId = $inputItem->referenceInvoiceItemId;
                        } elseif (is_array($inputItem)) {
                            $refItemId = $inputItem['reference_invoice_item_id'] ?? ($inputItem['referenceInvoiceItemId'] ?? null);
                        }

                        GstInvoiceItem::create([
                            'gst_invoice_id' => $invoice->id,
                            'reference_invoice_item_id' => $refItemId,
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

                    $updateData['gross_taxable'] = $summaryData->summary->grossTaxable;
                    $updateData['discount'] = $summaryData->summary->discount;
                    $updateData['subtotal'] = $summaryData->summary->subtotal;
                    $updateData['cgst_amount'] = $summaryData->summary->cgstAmount;
                    $updateData['sgst_amount'] = $summaryData->summary->sgstAmount;
                    $updateData['igst_amount'] = $summaryData->summary->igstAmount;
                    $updateData['gst_amount'] = $summaryData->summary->gstAmount;
                    $updateData['round_off'] = $summaryData->summary->roundOff;
                    $updateData['total'] = $summaryData->summary->total;
                    $updateData['due_amount'] = max(0.00, round($summaryData->summary->total - (float)$invoice->paid_amount, 2));

                    if ($summaryData->posStateCode) {
                        $updateData['pos_state_code'] = $summaryData->posStateCode;
                    }
                    if ($summaryData->posStateName) {
                        $updateData['pos_state_name'] = $summaryData->posStateName;
                    }
                    $updateData['is_interstate'] = $summaryData->isInterstate;
                    $updateData['is_reverse_charge'] = $summaryData->isReverseCharge;
                    $updateData['discount_mode'] = $summaryData->discountMode ?? 'bill';
                }

                if (!empty($additionalAttributes)) {
                    $updateData = array_merge($updateData, $additionalAttributes);
                }

                if (!empty($updateData)) {
                    $invoice->update($updateData);
                }

                $invoice->refresh();
                $structuredDTO = $invoice->toStructuredData();
                $invoice->updateQuietly(['billing_details' => $structuredDTO->toArray()]);

                return $invoice;
            });
        });
    }

    /**
     * Update invoice payment summary derived fields.
     */
    public function updatePaymentSummary(GstInvoice $invoice, float $paidAmount): GstInvoice
    {
        if ($invoice->invoice_type !== InvoiceType::TAX_INVOICE) {
            throw new InvalidGstInvoiceException("Payment tracking is only applicable for Tax Invoices.");
        }

        $total = (float)$invoice->total;
        $paid = round(max(0.00, $paidAmount), 2);
        $due = max(0.00, round($total - $paid, 2));

        $previousStatus = $invoice->payment_status?->value ?? PaymentStatus::UNPAID->value;

        if ($paid >= $total) {
            $newStatus = PaymentStatus::PAID->value;
        } elseif ($paid > 0) {
            $newStatus = PaymentStatus::PARTIALLY_PAID->value;
        } else {
            $newStatus = PaymentStatus::UNPAID->value;
        }

        event(new InvoicePaymentStatusChanging($invoice, $newStatus, $paid));

        $invoice->updateQuietly([
            'paid_amount' => $paid,
            'due_amount' => $due,
            'payment_status' => $newStatus,
        ]);

        $invoice->refresh();
        $structured = $invoice->toStructuredData();
        $invoice->updateQuietly(['billing_details' => $structured->toArray()]);

        event(new InvoicePaymentStatusChanged($invoice, $previousStatus, $newStatus));

        if ($newStatus === PaymentStatus::PAID->value) {
            event(new InvoicePaid($invoice, ['paid_amount' => $paid]));
        } elseif ($newStatus === PaymentStatus::PARTIALLY_PAID->value) {
            event(new InvoicePartiallyPaid($invoice, $paid, $due));
        }

        return $invoice;
    }

    /**
     * Legacy helper to mark invoice paid.
     */
    public function markAsPaid(GstInvoice $invoice, ?PaymentInput $paymentData = null): GstInvoice
    {
        $paymentData = $paymentData ?? new PaymentInput();
        $amount = $paymentData->amount !== null ? $paymentData->amount : (float)$invoice->total;

        return $this->updatePaymentSummary($invoice, (float)$invoice->paid_amount + $amount);
    }

    /**
     * Cancel an invoice or quotation.
     */
    public function cancelInvoice(GstInvoice $invoice, ?string $reason = null, mixed $cancelledBy = null): bool
    {
        return $invoice->cancelInvoice($reason, $cancelledBy);
    }

    protected function extractRecipientInputFromInvoice(GstInvoice $invoice): RecipientInput
    {
        return RecipientInput::make(
            name: $invoice->recipient_name,
            email: $invoice->recipient_email,
            phone: $invoice->recipient_phone,
            gstin: $invoice->recipient_gstin,
            pan: $invoice->recipient_pan,
            address: $invoice->recipient_address,
            city: $invoice->recipient_city,
            stateName: $invoice->recipient_state_name,
            stateCode: $invoice->recipient_state_code,
            pincode: $invoice->recipient_pincode
        );
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
                'shipping_pincode' => $recipient->shippingPincode ?? null,
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
