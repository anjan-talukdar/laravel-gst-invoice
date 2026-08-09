# Quotation Service API

[← Back to Documentation Index](../README.md)

The `QuotationService` manages pro-forma invoices and estimates.

## Instantiation

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$service = GstInvoice::quotations();
```

## `create()`

Creates a new Quotation with a default status of `sent`.

```php
public function create(mixed $recipient, array $items, ?InvoiceOptions $options = null): GstInvoice
```

## `accept()`

Marks the quotation as `accepted`.

```php
public function accept(GstInvoice $quotation): GstInvoice
```

## `reject()`

Marks the quotation as `rejected`.

```php
public function reject(GstInvoice $quotation, ?string $reason = null): GstInvoice
```

## `expire()`

Marks the quotation as `expired`.

```php
public function expire(GstInvoice $quotation): GstInvoice
```

## `convertToInvoice()`

Converts an `accepted` quotation into a new Tax Invoice.

```php
public function convertToInvoice(
    GstInvoice $quotation, 
    ?array $items = null, 
    ?InvoiceOptions $options = null
): GstInvoice
```

If `$items` or `$options` are `null`, it inherits them directly from the Quotation's snapshot.

## `createRevised()`

Generates a revised version of a quotation, linking the new quotation to the old one.

```php
public function createRevised(
    GstInvoice $quotation, 
    ?array $items = null, 
    ?InvoiceOptions $options = null
): GstInvoice
```

---
[← Back to Documentation Index](../README.md)
