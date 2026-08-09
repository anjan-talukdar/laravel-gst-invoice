# Tax Invoice Service API

[← Back to Documentation Index](../README.md)

The `TaxInvoiceService` handles standard GST Tax Invoices.

## Instantiation

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$service = GstInvoice::taxInvoice();
```

## `create()`

Creates a new Tax Invoice.

```php
public function create(
    mixed $recipient, 
    array $items, 
    ?InvoiceOptions $options = null
): \AnjanTalukdar\GstInvoice\Models\GstInvoice
```

### Parameters:
- `$recipient`: Can be a `RecipientInput` DTO, an Eloquent `Model`, or a class implementing `GstRecipientInterface`.
- `$items`: An array of `InvoiceItemInput` DTOs or plain associative arrays.
- `$options`: An `InvoiceOptions` DTO containing configuration overrides (e.g. `gstMode`, `posStateCode`).

### Example:

```php
$invoice = GstInvoice::taxInvoice()->create($recipient, [
    InvoiceItemInput::make('Software Maintenance', 25000.00)->gstRate(18.0)
]);
```

## `find()`

Retrieve an existing invoice.

```php
public function find(int $id): ?GstInvoice
```

## `issue()`

Transitions a draft Tax Invoice to `issued`.

```php
public function issue(GstInvoice $invoice): GstInvoice
```

## `cancel()`

Cancels a Tax Invoice.

```php
public function cancel(GstInvoice $invoice, ?string $reason = null, mixed $cancelledBy = null): bool
```

---
[← Back to Documentation Index](../README.md)
