# Quotations & Revisions

[← Back to Documentation Index](../README.md)

A **Quotation** (Pro-forma Invoice or Estimate) is a non-financial document. It does not establish tax liability until it is accepted and converted into a Tax Invoice.

## Creating a Quotation

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;

$quotation = GstInvoice::quotations()->create($recipient, $items);
// Status defaults to 'sent'
```

## Quotation Lifecycles

You can track quotation progression using fluent status methods:

```php
// Mark as accepted (Fires QuotationAccepted event)
GstInvoice::quotations()->accept($quotation);

// Mark as rejected (Fires QuotationRejected event)
GstInvoice::quotations()->reject($quotation, reason: 'Too expensive');

// Mark as expired (Fires QuotationExpired event)
GstInvoice::quotations()->expire($quotation);
```

## Converting to Tax Invoice

Once a Quotation is `accepted`, you can instantly convert it into a Tax Invoice.

```php
$taxInvoice = GstInvoice::quotations()->convertToInvoice($quotation);
```

This performs the following actions atomically:
1. Re-calculates taxes based on current config settings.
2. Generates a new `INV` sequence number.
3. Sets the `$taxInvoice->reference_invoice_id` to the `$quotation->id`.
4. Fires the `QuotationConvertedToInvoice` domain event.

*Note: Attempting to convert a quotation that is not `accepted` will throw an exception.*

## Revised Quotations

During negotiations, a customer might request changes. Instead of overwriting the original quotation, you can generate a **Revised Quotation** that retains a historical link to the previous version.

```php
$revisedItems = [
    InvoiceItemInput::make('Revised Software Dev', 1200.00)->discount(200.00)
];

$revisedQuotation = GstInvoice::quotations()->createRevised($quotation, $revisedItems);
```

The new quotation will point its `reference_invoice_id` to the older quotation, allowing you to build an audit trail of negotiation revisions.

When a revised quotation is created:
1. The new quotation is generated with `reference_invoice_id` set to the parent quotation's ID.
2. The parent quotation's status is **automatically set to `cancelled`** with `cancellation_reason = "Superseded by revised quotation #..."`.

## Revision Helper Methods

You can check if a quotation has subsequent revisions or is read-only using model helpers:

```php
// Returns true if a newer child document of the same type (QUOTATION) exists
$isSuperseded = $quotation->hasNewerRevision();

// Returns true if the quotation has a newer revision or is cancelled
$isReadOnly = $quotation->isReadOnly();
```

---
[← Back to Documentation Index](../README.md)
