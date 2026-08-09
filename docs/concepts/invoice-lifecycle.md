# Invoice Lifecycle & Statuses

[← Back to Documentation Index](../README.md)

Every generated document passes through various lifecycle stages tracked by the `status` column, mapped to the `AnjanTalukdar\GstInvoice\Enums\InvoiceStatus` enum.

## Available Statuses

- `draft`: The document is saved but not yet finalized. Can be edited or deleted.
- `issued`: The standard active state for a Tax Invoice, Credit/Debit Note, or Receipt Voucher.
- `sent`: (Mainly for Quotations) The document has been sent to the customer.
- `accepted`: (For Quotations) The customer has accepted the quotation.
- `rejected`: (For Quotations) The customer rejected the quotation.
- `expired`: (For Quotations) The quotation validity has lapsed.
- `applied`: (For Receipt Vouchers or Credit Notes) Indicates the amount has been fully applied to a tax invoice.
- `cancelled`: The document has been voided/cancelled.

## The Issuance Process

By default, creating a Tax Invoice sets its status to `issued`. Creating a Quotation sets its status to `sent`.

You can transition a document to `issued` manually:

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

GstInvoice::issueDocument($invoice);
```

## Cancellations

Under GST rules, once an invoice is issued, it cannot be deleted. If it was made by mistake, it must be **cancelled** (or a Credit Note must be issued if the supply has already taken place).

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

GstInvoice::cancelInvoice(
    invoice: $taxInvoice, 
    reason: 'Incorrect customer details selected.', 
    cancelledBy: auth()->id()
);
```

Cancelling an invoice updates its status to `cancelled`, sets the `cancelled_at` timestamp, and fires the `InvoiceCancelled` domain event.

---
[← Back to Documentation Index](../README.md)
