# Tutorial: Handling Partial Payments

[← Back to Documentation Index](../README.md)

Sometimes clients pay an invoice in multiple installments. The library handles the math and status transitions for you.

## Scenario

You issued an invoice for ₹11,800. The customer pays ₹5000 upfront.

## 1. Log the Initial Payment

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

// We assume $invoice is a GstInvoice model with total = 11800.00
GstInvoice::updatePaymentSummary($invoice, paidAmount: 5000.00);

echo $invoice->payment_status->value; // 'partially_paid'
echo $invoice->due_amount;           // 6800.00
```

*The library fires the `InvoicePartiallyPaid` event.*

## 2. Log the Final Payment

Two weeks later, the customer pays the remaining ₹6800. You must pass the **total cumulative paid amount** (₹11,800) to the engine:

```php
// Old paid amount (5000) + New payment (6800)
$totalCumulativePaid = 11800.00;

GstInvoice::updatePaymentSummary($invoice, paidAmount: $totalCumulativePaid);

echo $invoice->payment_status->value; // 'paid'
echo $invoice->due_amount;           // 0.00
```

*The library fires the `InvoicePaid` event.*

---
[← Back to Documentation Index](../README.md)
