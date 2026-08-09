# Receipt Voucher Service API

[← Back to Documentation Index](../README.md)

The `ReceiptVoucherService` handles the creation of advance tax Receipt Vouchers and simple payment receipts.

## Instantiation

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$service = GstInvoice::receiptVouchers();
```

## `create()`

Creates a generic Receipt Voucher.

```php
public function create(
    mixed $recipient, 
    array $items, 
    ?InvoiceOptions $options = null
): GstInvoice
```

## `createAdvance()`

A convenience wrapper around `create()` that specifically fires the `AdvanceReceived` domain event.

```php
public function createAdvance(
    mixed $recipient, 
    array $items, 
    ?InvoiceOptions $options = null
): GstInvoice
```

Use this method when receiving a monetary advance prior to supplying goods or services, triggering GST liability on the advance amount.

---
[← Back to Documentation Index](../README.md)
