# Tutorial: Managing Advance Payments

[← Back to Documentation Index](../README.md)

If you require an upfront payment before starting work, you must issue a Receipt Voucher to recognize the GST liability on the advance.

## 1. Issue Receipt Voucher

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$advanceVoucher = GstInvoice::receiptVouchers()->createAdvance($recipient, [
    ['description' => 'Advance for SEO Services', 'unit_price' => 20000.00, 'gst_rate' => 18.0]
]);

// This is legally compliant and will appear in GSTR-1 Advance Tables.
```

## 2. Issue Final Tax Invoice

Once the work is completed (say, total value is ₹50,000), you issue a final Tax Invoice. You should reference the advance on a separate negative line item or adjust the total due manually depending on your accounting preference.

*Note: The library does not automatically net-off Receipt Vouchers against Tax Invoices. Your application must orchestrate the application of advance credits.*

```php
$finalInvoice = GstInvoice::taxInvoice()->create($recipient, [
    ['description' => 'SEO Services Final Billing', 'unit_price' => 50000.00, 'gst_rate' => 18.0]
]);

// Mark the 20,000 advance as paid against the 50,000 invoice
GstInvoice::updatePaymentSummary($finalInvoice, paidAmount: 20000.00);
```

---
[← Back to Documentation Index](../README.md)
