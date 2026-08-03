<?php

namespace AnjanTalukdar\GstInvoice\Traits;

use AnjanTalukdar\GstInvoice\Models\GstInvoice;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasGstInvoices
{
    public function gstInvoices(): MorphMany
    {
        return $this->morphMany(GstInvoice::class, 'recipient');
    }
}
