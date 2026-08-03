<?php

namespace AnjanTalukdar\GstInvoice\Traits;

use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanBeInvoiced
{
    public function invoices(): MorphMany
    {
        return $this->morphMany(GstInvoice::class, 'invoicable');
    }
}
