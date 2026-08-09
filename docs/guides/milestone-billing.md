# Tutorial: Milestone Billing

[← Back to Documentation Index](../README.md)

Milestone billing is a mix between partial payments and quotation conversions.

## The Workflow

1. You create a master Quotation for a ₹1,00,000 project.
2. The customer accepts it.
3. You do not convert it into a single ₹1,00,000 Tax Invoice, because you are billing in 4 milestones of ₹25,000 each.
4. Instead, you create 4 distinct Tax Invoices over time, each referencing the Quotation ID.

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Data\InvoiceOptions;

$options = InvoiceOptions::make()->referenceInvoiceId($quotation->id);

$milestone1 = GstInvoice::taxInvoice()->create($recipient, [
    ['description' => 'Milestone 1: Design Phase', 'unit_price' => 25000.00]
], $options);

// A month later...
$milestone2 = GstInvoice::taxInvoice()->create($recipient, [
    ['description' => 'Milestone 2: Frontend Dev', 'unit_price' => 25000.00]
], $options);
```

This keeps your tax liabilities strictly tied to the date you actually issued the milestone invoice, preventing you from having to pay GST on the entire ₹1,00,000 upfront before you've received the money or completed the supply.

---
[← Back to Documentation Index](../README.md)
