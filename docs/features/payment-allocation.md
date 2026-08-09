# Payment Allocations & Tracking

[← Back to Documentation Index](../README.md)

This section acts as a reference to [Payment Architecture](../concepts/payments.md).

## Updating Payments

To update the payment progress on an invoice, call `updatePaymentSummary()` with the **cumulative total amount** paid to date:

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

GstInvoice::updatePaymentSummary($invoice, paidAmount: 1500.00);
```

## Internal Triggers

When `updatePaymentSummary()` runs, it performs the following:
1. Calculates `due_amount = total - paidAmount`.
2. Evaluates the new `PaymentStatus`:
   - `paidAmount >= total` -> `PAID`
   - `paidAmount > 0` -> `PARTIALLY_PAID`
   - `paidAmount == 0` -> `UNPAID`
3. Fires the `InvoicePaymentStatusChanging` event.
4. Updates the database without triggering normal Eloquent `$invoice->save()` events to prevent false `InvoiceUpdated` triggers.
5. Updates the JSON `billing_details` snapshot.
6. Fires `InvoicePaymentStatusChanged`.
7. Fires `InvoicePaid` or `InvoicePartiallyPaid` if applicable.

---
[← Back to Documentation Index](../README.md)
