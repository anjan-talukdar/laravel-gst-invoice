<?php

namespace AnjanTalukdar\GstInvoice\Models;

use AnjanTalukdar\GstInvoice\Data\InvoiceData;
use AnjanTalukdar\GstInvoice\Enums\InvoiceStatus;
use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Enums\PaymentStatus;
use AnjanTalukdar\GstInvoice\Enums\PaymentTerm;
use AnjanTalukdar\GstInvoice\Events\InvoiceCancelled;
use AnjanTalukdar\GstInvoice\Events\InvoiceCancelling;
use AnjanTalukdar\GstInvoice\Events\InvoiceDeleted;
use AnjanTalukdar\GstInvoice\Events\InvoiceUpdated;
use AnjanTalukdar\GstInvoice\Events\InvoiceUpdating;
use AnjanTalukdar\GstInvoice\Exceptions\InvoiceImmutableException;
use AnjanTalukdar\GstInvoice\Helpers\NumberToWords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class GstInvoice extends Model
{
    protected $table = 'gst_invoices';

    protected $fillable = [
        'invoice_type',
        'reference_invoice_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'payment_terms',
        'invoicable_type',
        'invoicable_id',
        'recipient_type',
        'recipient_id',

        'supplier_name',
        'supplier_gstin',
        'supplier_pan',
        'supplier_address',
        'supplier_city',
        'supplier_state_name',
        'supplier_state_code',
        'supplier_pincode',
        'supplier_email',
        'supplier_phone',

        'recipient_name',
        'recipient_email',
        'recipient_phone',
        'recipient_gstin',
        'recipient_pan',
        'recipient_address',
        'recipient_city',
        'recipient_state_name',
        'recipient_state_code',
        'recipient_pincode',

        'pos_state_name',
        'pos_state_code',
        'is_interstate',
        'is_reverse_charge',
        'discount_mode',

        'gross_taxable',
        'discount',
        'subtotal',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'gst_amount',
        'round_off',
        'total',
        'currency',
        'paid_amount',
        'due_amount',

        'payment_status',
        'status',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',

        'billing_details',
        'remark',
        'created_by',
    ];

    protected $casts = [
        'invoice_type' => InvoiceType::class,
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_terms' => PaymentTerm::class,
        'payment_status' => PaymentStatus::class,
        'status' => InvoiceStatus::class,
        'is_interstate' => 'boolean',
        'is_reverse_charge' => 'boolean',
        'gross_taxable' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'round_off' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'billing_details' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function (GstInvoice $invoice) {
            $financialFields = [
                'gross_taxable',
                'discount',
                'subtotal',
                'cgst_amount',
                'sgst_amount',
                'igst_amount',
                'gst_amount',
                'round_off',
                'total',
                'supplier_name',
                'supplier_gstin'
            ];

            foreach ($financialFields as $field) {
                if ($invoice->isDirty($field)) {
                    throw new InvoiceImmutableException($invoice->invoice_number);
                }
            }

            event(new InvoiceUpdating($invoice, $invoice->getDirty()));
        });

        static::updated(function (GstInvoice $invoice) {
            event(new InvoiceUpdated($invoice));
        });

        static::deleted(function (GstInvoice $invoice) {
            event(new InvoiceDeleted($invoice));
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(GstInvoiceItem::class, 'gst_invoice_id')->orderBy('sort_order');
    }

    public function invoicable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    public function referenceInvoice(): BelongsTo
    {
        return $this->belongsTo(GstInvoice::class, 'reference_invoice_id');
    }

    public function childInvoices(): HasMany
    {
        return $this->hasMany(GstInvoice::class, 'reference_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(GstInvoice::class, 'reference_invoice_id')
            ->where('invoice_type', InvoiceType::CREDIT_NOTE->value);
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(GstInvoice::class, 'reference_invoice_id')
            ->where('invoice_type', InvoiceType::DEBIT_NOTE->value);
    }

    public function isActive(): bool
    {
        return $this->status !== InvoiceStatus::CANCELLED;
    }

    public function isCancelled(): bool
    {
        return $this->status === InvoiceStatus::CANCELLED;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::PAID;
    }

    public function getIsIntraStateAttribute(): bool
    {
        return !$this->is_interstate;
    }

    public function getTotalInWordsAttribute(): string
    {
        return NumberToWords::toWords((float)$this->total, $this->currency ?: 'INR');
    }

    public function getBankDetailsAttribute(): array
    {
        return $this->billing_details['supplier']['bank_details']
            ?? $this->billing_details['bank_details']
            ?? config('gst-invoice.supplier.bank_details', []);
    }

    public function toStructuredData(): InvoiceData
    {
        $itemsArr = $this->items->map(fn(GstInvoiceItem $item) => [
            'description' => $item->description,
            'code_type' => $item->code_type?->value ?? 'SAC',
            'code' => $item->code,
            'tax_category' => $item->tax_category?->value ?? 'taxable',
            'quantity' => (float)$item->quantity,
            'unit' => $item->unit,
            'unit_price' => (float)$item->unit_price,
            'item_discount' => (float)$item->item_discount,
            'bill_discount' => (float)$item->bill_discount,
            'taxable_amount' => (float)$item->taxable_amount,
            'gst_rate' => (float)$item->gst_rate,
            'cgst_amount' => (float)$item->cgst_amount,
            'sgst_amount' => (float)$item->sgst_amount,
            'igst_amount' => (float)$item->igst_amount,
            'gst_amount' => (float)($item->cgst_amount + $item->sgst_amount + $item->igst_amount),
            'total_amount' => (float)$item->total_amount,
            'reference_invoice_item_id' => $item->reference_invoice_item_id,
            'meta_data' => $item->meta_data,
            'sort_order' => $item->sort_order,
        ])->toArray();

        $arr = [
            'schema_version' => '1.0',
            'invoice_type' => $this->invoice_type?->value ?? 'tax_invoice',
            'invoice_type_label' => $this->invoice_type?->label() ?? 'Tax Invoice',
            'reference_invoice_id' => $this->reference_invoice_id,
            'reference_invoice_number' => $this->referenceInvoice?->invoice_number,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'payment_terms' => $this->payment_terms?->value ?? 'due_on_receipt',
            'is_reverse_charge' => (bool)$this->is_reverse_charge,
            'discount_mode' => $this->discount_mode ?? ($this->billing_details['discount_mode'] ?? 'bill'),
            'pos_state_name' => $this->pos_state_name,
            'pos_state_code' => $this->pos_state_code,
            'is_interstate' => (bool)$this->is_interstate,
            'supplier' => [
                'name' => $this->supplier_name,
                'email' => $this->supplier_email,
                'phone' => $this->supplier_phone,
                'gstin' => $this->supplier_gstin,
                'pan' => $this->supplier_pan,
                'address' => $this->supplier_address,
                'city' => $this->supplier_city,
                'state_name' => $this->supplier_state_name,
                'state_code' => $this->supplier_state_code,
                'pincode' => $this->supplier_pincode,
                'bank_details' => $this->bank_details,
            ],
            'recipient' => [
                'name' => $this->recipient_name,
                'email' => $this->recipient_email,
                'phone' => $this->recipient_phone,
                'gstin' => $this->recipient_gstin,
                'pan' => $this->recipient_pan,
                'address' => $this->recipient_address,
                'city' => $this->recipient_city,
                'state_name' => $this->recipient_state_name,
                'state_code' => $this->recipient_state_code,
                'pincode' => $this->recipient_pincode,
            ],
            'items' => $itemsArr,
            'summary' => [
                'gross_taxable' => (float)$this->gross_taxable,
                'discount' => (float)$this->discount,
                'subtotal' => (float)$this->subtotal,
                'cgst_amount' => (float)$this->cgst_amount,
                'sgst_amount' => (float)$this->sgst_amount,
                'igst_amount' => (float)$this->igst_amount,
                'gst_amount' => (float)$this->gst_amount,
                'round_off' => (float)$this->round_off,
                'total' => (float)$this->total,
                'paid_amount' => (float)$this->paid_amount,
                'due_amount' => (float)$this->due_amount,
                'paid' => (float)$this->paid_amount,
                'due' => (float)$this->due_amount,
            ],
            'gst_slabs' => [],
            'amount_in_words' => $this->total_in_words,
            'bank_details' => $this->bank_details,
            'currency' => $this->currency ?: 'INR',
            'remark' => $this->remark,
            'status' => $this->status?->value ?? 'issued',
            'payment_status' => $this->payment_status?->value,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancelled_by' => $this->cancelled_by,
            'cancellation_reason' => $this->cancellation_reason,
        ];

        return InvoiceData::fromArray($arr);
    }

    public function cancelInvoice(?string $reason = null, mixed $cancelledBy = null): bool
    {
        event(new InvoiceCancelling($this, $reason, $cancelledBy));

        $updated = $this->updateQuietly([
            'status' => InvoiceStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancelled_by' => (string)($cancelledBy ?? Auth::id() ?? 'system'),
            'cancellation_reason' => $reason ?? 'Cancelled by user',
        ]);

        if ($updated) {
            $this->refresh();
            event(new InvoiceCancelled($this, $reason, $cancelledBy));
        }

        return $updated;
    }
}
