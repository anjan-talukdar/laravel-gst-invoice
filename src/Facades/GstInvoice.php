<?php

namespace AnjanTalukdar\GstInvoice\Facades;

use AnjanTalukdar\GstInvoice\Contracts\GstRecipientInterface;
use AnjanTalukdar\GstInvoice\Data\BillingSummaryData;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Data\PaymentInput;
use AnjanTalukdar\GstInvoice\Data\RecipientInput;
use AnjanTalukdar\GstInvoice\Models\GstInvoice as GstInvoiceModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static BillingSummaryData calculateSummary(InvoiceItemInput[] $items, ?InvoiceOptions $options = null)
 * @method static GstInvoiceModel createInvoice(RecipientInput|GstRecipientInterface|Model $recipient, InvoiceItemInput[] $items, ?InvoiceOptions $options = null)
 * @method static GstInvoiceModel markAsPaid(GstInvoiceModel $invoice, ?PaymentInput $paymentData = null)
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
