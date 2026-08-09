# Payment Tracking API

[← Back to Documentation Index](../README.md)

The payment tracking logic is accessed directly from the `GstInvoice` facade rather than a sub-service, because it applies only to Tax Invoices.

## `updatePaymentSummary()`

Updates the cumulative paid amount and recalculates the due balance and payment status.

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

public function updatePaymentSummary(
    GstInvoice $invoice, 
    float $paidAmount
): GstInvoice
```

### Parameters:
- `$invoice`: The `GstInvoice` instance (must be of type `tax_invoice`).
- `$paidAmount`: The **total cumulative amount paid to date** (not just the incremental payment).

### Throws:
- `InvalidGstInvoiceException` if the document is not a Tax Invoice.

### Dispatches Events:
- `InvoicePaymentStatusChanging` (before update)
- `InvoicePaymentStatusChanged` (after update if status changed)
- `InvoicePaid` (if `$paidAmount` >= `$invoice->total`)
- `InvoicePartiallyPaid` (if `$paidAmount` > 0 but < `$invoice->total`)

---
[← Back to Documentation Index](../README.md)
