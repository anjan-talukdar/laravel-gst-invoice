# Tax Inclusive vs Exclusive Pricing

[← Back to Documentation Index](../README.md)

The library supports both Tax Inclusive and Tax Exclusive calculation models, easily toggled via the `GstMode` enum in the `InvoiceOptions`.

## Tax Exclusive (Default B2B)

In `EXCLUSIVE` mode, the line item `unit_price` is considered the base taxable value. GST is calculated on top of this value.

```php
use AnjanTalukdar\GstInvoice\Enums\GstMode;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;

$items = [
    InvoiceItemInput::make('Product A', 100.00)->gstRate(18.0)
];

$options = InvoiceOptions::make(GstMode::EXCLUSIVE);

// Result:
// Subtotal = 100.00
// GST = 18.00
// Total = 118.00
```

## Tax Inclusive (Default B2C / Retail)

In `INCLUSIVE` mode, the line item `unit_price` represents the final price the customer pays. The engine mathematically reverse-calculates the base taxable value and the GST amount.

```php
use AnjanTalukdar\GstInvoice\Enums\GstMode;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;

$items = [
    InvoiceItemInput::make('Product A', 118.00)->gstRate(18.0)
];

$options = InvoiceOptions::make(GstMode::INCLUSIVE);

// Result:
// Subtotal = 100.00
// GST = 18.00
// Total = 118.00
```

*Note: The default mode can be configured globally in `config/gst-invoice.php` under the `gst_mode` key.*

---
[← Back to Documentation Index](../README.md)
