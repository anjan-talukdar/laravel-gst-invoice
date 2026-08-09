<?php

namespace AnjanTalukdar\GstInvoice\Models;

use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Model;

class InvoiceNumberSequence extends Model
{
    protected $table = 'invoice_number_sequences';

    protected $fillable = [
        'invoice_type',
        'financial_year',
        'prefix',
        'last_number',
    ];

    protected $casts = [
        'invoice_type' => InvoiceType::class,
        'last_number' => 'integer',
    ];
}
