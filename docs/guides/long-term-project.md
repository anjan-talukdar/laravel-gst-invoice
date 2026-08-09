# Tutorial: Long-Term Project Contracts

[← Back to Documentation Index](../README.md)

For continuous supply of services (e.g., a 6-month consulting contract), you usually issue a Quotation first, then issue multiple Tax Invoices periodically.

## 1. Issue the Quotation

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$quotation = GstInvoice::quotations()->create($recipient, [
    ['description' => '6 Month Retainer', 'unit_price' => 600000.00, 'gst_rate' => 18.0]
]);

GstInvoice::quotations()->accept($quotation);
```

## 2. Issue Monthly Tax Invoices

Instead of converting the entire quotation at once, you manually issue a Tax Invoice every month referencing the original quotation contract.

```php
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;

// Month 1 Billing
$month1Invoice = GstInvoice::taxInvoice()->create($recipient, [
    ['description' => 'Retainer - Month 1', 'unit_price' => 100000.00, 'gst_rate' => 18.0]
], InvoiceOptions::make()->referenceInvoiceId($quotation->id));

// Month 2 Billing
$month2Invoice = GstInvoice::taxInvoice()->create($recipient, [
    ['description' => 'Retainer - Month 2', 'unit_price' => 100000.00, 'gst_rate' => 18.0]
], InvoiceOptions::make()->referenceInvoiceId($quotation->id));
```

This ensures that while the customer receives smaller, periodic tax invoices, your database maintains a clear audit trail linking them back to the master `QT` document.

---
[← Back to Documentation Index](../README.md)
