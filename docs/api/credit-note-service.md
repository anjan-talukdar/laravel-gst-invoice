# Credit Note Service API

[← Back to Documentation Index](../README.md)

The `CreditNoteService` handles the creation and validation of Credit Notes.

## Instantiation

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$service = GstInvoice::creditNotes();
```

## `create()`

Creates a new Credit Note linked to an original Tax Invoice.

```php
public function create(
    GstInvoice $originalInvoice, 
    array $items, 
    ?InvoiceOptions $options = null
): GstInvoice
```

**Note:** Unlike `taxInvoice()->create()`, the first parameter here is the original `$originalInvoice` model, not a `$recipient`. The recipient details are automatically inherited from the original invoice.

### Validation:
The service automatically validates that the `total` of the Credit Note does not exceed the remaining adjustable amount of the `$originalInvoice`.

## `getRemainingAdjustableAmount()`

Calculates how much credit can still be issued against an invoice or a specific line item.

```php
public function getRemainingAdjustableAmount(GstInvoice $invoice, ?int $itemId = null): float
```

- If `$itemId` is `null`, it returns the remaining adjustable `total` of the entire invoice.
- If `$itemId` is provided, it returns the remaining adjustable `total_amount` for that specific line item.

---
[← Back to Documentation Index](../README.md)
