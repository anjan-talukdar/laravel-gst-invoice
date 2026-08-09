<?php

namespace AnjanTalukdar\GstInvoice\Models;

use AnjanTalukdar\GstInvoice\Enums\CodeType;
use AnjanTalukdar\GstInvoice\Enums\TaxCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstInvoiceItem extends Model
{
    protected $table = 'gst_invoice_items';

    protected $fillable = [
        'gst_invoice_id',
        'reference_invoice_item_id',
        'description',
        'code_type',
        'code',
        'tax_category',
        'quantity',
        'unit',
        'unit_price',
        'item_discount',
        'bill_discount',
        'taxable_amount',
        'gst_rate',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'sort_order',
        'meta_data',
    ];

    protected $casts = [
        'code_type' => CodeType::class,
        'tax_category' => TaxCategory::class,
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'item_discount' => 'decimal:2',
        'bill_discount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sort_order' => 'integer',
        'meta_data' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(GstInvoice::class, 'gst_invoice_id');
    }

    public function referenceItem(): BelongsTo
    {
        return $this->belongsTo(GstInvoiceItem::class, 'reference_invoice_item_id');
    }
}
