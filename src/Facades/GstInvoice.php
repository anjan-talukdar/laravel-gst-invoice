<?php

namespace AnjanTalukdar\GstInvoice\Facades;

use AnjanTalukdar\GstInvoice\Data\BillingSummaryData;
use AnjanTalukdar\GstInvoice\Models\GstInvoice as GstInvoiceModel;
use Illuminate\Support\Facades\Facade;

/**
 * @method static BillingSummaryData calculateSummary(array $items, array $options = [])
 * @method static GstInvoiceModel createInvoice(mixed $recipient, array $items, array $options = [])
 * @method static GstInvoiceModel markAsPaid(GstInvoiceModel $invoice, array $paymentData = [])
 * @method static bool cancelInvoice(GstInvoiceModel $invoice, ?string $reason = null, mixed $cancelledBy = null)
 *
 * @see \AnjanTalukdar\GstInvoice\Services\GstInvoiceService
 */
class GstInvoice extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'gst-invoice';
    }
}
