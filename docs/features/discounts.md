# Discounts Handling

[← Back to Documentation Index](../README.md)

The engine supports two distinct methods for applying discounts: **Item-Level Discounts** and **Proportional Bill-Level Discounts**.

## 1. Item-Level Discounts

You can apply a specific discount amount directly to a line item. The tax is calculated on the value *after* the discount is subtracted.

```php
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;

$items = [
    InvoiceItemInput::make('Laptop', 50000.00)
        ->discount(5000.00) // ₹5000 off this specific item
        ->gstRate(18.0)
];

// Taxable Value = 45000.00
// GST (18%) = 8100.00
```

## 2. Proportional Bill-Level Discounts

If you apply a generic coupon code (e.g., `SAVE1000`) to the entire cart, the engine must proportionally distribute the discount across all line items based on their weight relative to the gross total. 

This is required by GST law because different items in the cart might attract different GST rates (e.g., 5% and 18%), and you cannot arbitrarily apply the discount to the lower tax bracket to save money.

```php
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;

$options = InvoiceOptions::make()->discount(1000.00);
```

The engine mathematically allocates fractions of the `1000.00` discount to each line item behind the scenes, ensuring mathematically accurate taxable values and tax splits per HSN/SAC code.

---
[← Back to Documentation Index](../README.md)
