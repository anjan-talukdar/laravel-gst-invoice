# Tutorial: Issuing a Basic Invoice

[← Back to Documentation Index](../README.md)

This guide walks you through the simplest workflow: A customer buys a digital service and you issue a B2C Tax Invoice.

## 1. Define the Recipient

The recipient can be a plain array or a `RecipientInput` DTO.

```php
use AnjanTalukdar\GstInvoice\Data\RecipientInput;

$recipient = RecipientInput::make('John Doe')
    ->email('john@example.com')
    ->phone('9876543210')
    ->stateCode('27'); // Maharashtra
```

## 2. Define the Line Items

```php
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;

$items = [
    InvoiceItemInput::make('Standard Web Hosting', 5000.00)->gstRate(18.0)
];
```

## 3. Generate the Invoice

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$invoice = GstInvoice::taxInvoice()->create($recipient, $items);
```

Behind the scenes:
1. The engine looks at your `config/gst-invoice.php` supplier state.
2. It compares it to John Doe's state (`27`). If they differ, it charges IGST. If they match, it charges CGST + SGST.
3. It allocates a sequence number (e.g., `INV/26-27/00001`).
4. It sets the status to `issued` and payment status to `unpaid`.

## 4. Render the PDF

```php
$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
    'invoices.standard', 
    ['invoice' => $invoice->toStructuredData()->toArray()]
);

return $pdf->download('Invoice_' . $invoice->invoice_number . '.pdf');
```

---
[← Back to Documentation Index](../README.md)
