# Debit Note Service API

[← Back to Documentation Index](../README.md)

The `DebitNoteService` handles the creation of Debit Notes, which increase the tax liability of an original Tax Invoice.

## Instantiation

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$service = GstInvoice::debitNotes();
```

## `create()`

Creates a new Debit Note linked to an original Tax Invoice.

```php
public function create(
    GstInvoice $originalInvoice, 
    array $items, 
    ?InvoiceOptions $options = null
): GstInvoice
```

**Note:** Just like Credit Notes, the first parameter is the original `$originalInvoice` model. The recipient details are automatically inherited from the original invoice.

---
[← Back to Documentation Index](../README.md)
