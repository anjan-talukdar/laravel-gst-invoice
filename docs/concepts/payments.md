# Payment Architecture

[← Back to Documentation Index](../README.md)

## Design Philosophy

The Laravel GST Invoice library handles **invoicing** and **GST compliance**. It intentionally **does not** manage complex payment transaction tables (e.g., storing Razorpay/Stripe IDs, refunds, dispute statuses, wallet ledgers).

Every application handles payments differently. Instead of forcing a rigid payment table schema onto your application, this library takes a decoupled approach:

1. **Convenience Fields**: The `gst_invoices` table has `paid_amount`, `due_amount`, and `payment_status` columns.
2. **Payment Service Engine**: You tell the library how much has been paid against an invoice, and it calculates the remaining dues and statuses.
3. **Domain Events**: The library fires events when payment statuses change, allowing your application to update its own custom ledgers or sync with third-party gateways.

## Payment Statuses

Mapped to `AnjanTalukdar\GstInvoice\Enums\PaymentStatus`:
- `unpaid`: `paid_amount` is 0.
- `partially_paid`: `paid_amount` > 0, but less than `total`.
- `paid`: `paid_amount` >= `total`.
- `overdue`: The current date has passed the `due_date` and the status is not `paid`.

## Updating Payment Summaries

When your application successfully processes a payment (e.g., via a Stripe/Razorpay webhook), you report the **cumulative total paid** to the engine:

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

// If the customer just paid ₹5000 against a ₹10000 invoice:
GstInvoice::updatePaymentSummary($taxInvoice, paidAmount: 5000.00);

// $taxInvoice->payment_status->value is now 'partially_paid'
// $taxInvoice->due_amount is 5000.00
```

> [!IMPORTANT]
> The `paidAmount` parameter in `updatePaymentSummary` expects the **total amount paid to date**, not just the incremental transaction amount. If they pay another ₹5000 later, you pass `10000.00`.

## Domain Events

When you update the payment summary, the engine automatically fires lifecycle events:

- `InvoicePaymentStatusChanging`
- `InvoicePaymentStatusChanged`
- `InvoicePartiallyPaid`
- `InvoicePaid`

You can listen to these in your `EventServiceProvider` to trigger email receipts or software provisioning:

```php
protected $listen = [
    InvoicePaid::class => [
        UnlockProFeaturesListener::class,
    ],
];
```

---
[← Back to Documentation Index](../README.md)
