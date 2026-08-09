# GST Calculation Engine

[← Back to Documentation Index](../README.md)

The core of the library is a highly accurate tax calculation engine capable of resolving complex GST mathematical rules. 

You can use the calculation engine independently of the database to generate real-time totals for checkout pages and shopping carts.

## Standalone Checkout Calculation

To calculate totals without saving anything to the database, use `GstInvoice::calculateSummary()`. It returns a `BillingSummaryData` object containing `items` (array of processed line items) and `summary` (total aggregates).

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;

$items = [
    InvoiceItemInput::make('Web Hosting (1 Year)', 10000.00)->gstRate(18.0)
];

// Provide POS data via options
$options = InvoiceOptions::make()
    ->supplierStateCode('27') // Maharashtra
    ->posStateCode('29');     // Karnataka

$summaryData = GstInvoice::calculateSummary($items, $options);

echo $summaryData->summary->subtotal;   // 10000.00
echo $summaryData->summary->igstAmount; // 1800.00
echo $summaryData->summary->total;      // 11800.00
```

## Place of Supply (POS) Engine

The engine automatically determines whether a transaction attracts **Intra-State (CGST + SGST)** or **Inter-State (IGST)** tax based on the 2-digit Indian State Codes.

1. **Intra-State**: `supplierStateCode === posStateCode`
2. **Inter-State**: `supplierStateCode !== posStateCode`

You can also explicitly override this behavior:

```php
$options = InvoiceOptions::make()->isInterstate(true);
```

## Odd Paisa Tax Weightage

When dividing an odd tax amount between CGST and SGST, floating point precision often results in a lost or gained paisa.

For example, 18% GST on ₹555.00 is ₹99.90.
Dividing by 2 gives ₹49.95. All good.
But 18% GST on ₹555.05 is ₹99.909... rounded to ₹99.91.
Dividing ₹99.91 by 2 is ₹49.955. 

If both round up, it becomes 49.96 + 49.96 = 99.92 (Incorrect: 1 paisa extra).
If both round down, it becomes 49.95 + 49.95 = 99.90 (Incorrect: 1 paisa lost).

The library handles this gracefully via the `odd_paisa_weightage` configuration. It assigns the exact half to both, and adds the remaining 1 paisa to either CGST or SGST based on your configuration setting, ensuring `CGST + SGST === Total GST` down to the exact paisa.

---
[← Back to Documentation Index](../README.md)
