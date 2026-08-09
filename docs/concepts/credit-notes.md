# Credit Notes

[← Back to Documentation Index](../README.md)

Under Section 34 of the CGST Act, a **Credit Note** (`CN`) is issued by the supplier to reduce the tax liability of an originally issued Tax Invoice.

Common scenarios include:
1. Sales returns (goods returned).
2. Deficiency in services (partial refunds).
3. Post-sale discounts agreed upon beforehand.
4. Correction of accidental over-billing.

## Creating a Credit Note

A Credit Note **must** reference an original Tax Invoice (`reference_invoice_id`).

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;

// The original invoice we are crediting
$taxInvoice = GstInvoice::taxInvoice()->find(1);

// Issue credit for a specific line item
$cnItems = [
    InvoiceItemInput::make('Refund for defective item', 1500.00, 1.0)
        ->referenceInvoiceItemId($originalItemId) // Link to original line item
];

$creditNote = GstInvoice::creditNotes()->create($taxInvoice, $cnItems);
```

## Adjustable Balance Limits

The package automatically protects you from issuing more credit notes than the original invoice value.

Before creating a credit note, you can check the remaining adjustable amount:

```php
// Remaining adjustable amount for the entire invoice:
$balance = GstInvoice::creditNotes()->getRemainingAdjustableAmount($taxInvoice);

// Remaining adjustable amount for a specific line item:
$itemBalance = GstInvoice::creditNotes()->getRemainingAdjustableAmount($taxInvoice, $itemId);
```

If you attempt to create a Credit Note that exceeds the `getRemainingAdjustableAmount()`, the engine will throw an `InvalidGstInvoiceException`.

---
[← Back to Documentation Index](../README.md)
