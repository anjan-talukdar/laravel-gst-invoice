# Advances & Receipt Vouchers

[← Back to Documentation Index](../README.md)

Under Section 31(3)(d) of the CGST Act, when a registered person receives an **advance payment** with respect to any supply of goods or services, they must issue a **Receipt Voucher** (RV). 

The liability to pay GST on advances applies primarily to **services**.

## Creating a Receipt Voucher

You can create an advance receipt voucher using the `ReceiptVoucherService`:

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;

$items = [
    InvoiceItemInput::make('Advance for Web Development Project', 50000.00)->gstRate(18.0)
];

$receiptVoucher = GstInvoice::receiptVouchers()->createAdvance($recipient, $items);
```

When you use `createAdvance()`, it internally sets the document type to `InvoiceType::RECEIPT_VOUCHER` and dispatches the `AdvanceReceived` domain event.

## GSTR-1 Compliance

Receipt Vouchers representing advances are automatically included in the GSTR-1 Compliance Data Exports under the **Advances Received (Table 11A)** section, helping your accountants easily file returns.

```php
$advanceData = GstInvoice::reports()->getGstr1AdvanceData('2026-08');
```

---
[← Back to Documentation Index](../README.md)
