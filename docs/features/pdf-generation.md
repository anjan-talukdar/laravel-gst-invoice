# PDF Generation & Rendering

[← Back to Documentation Index](../README.md)

The library does **not** force a specific PDF engine (like DomPDF, Snappy, or Browsershot) upon your application. 

Instead, it acts as a structured data provider, allowing you to pass a completely parsed, calculation-ready Data Transfer Object (DTO) directly into your Blade templates, Vue components, or React frontends.

## The Structured Data DTO

You can extract the fully resolved view data using:

```php
$dto = $invoice->toStructuredData();
```

This returns an `InvoiceData` object, which has heavily nested structured properties (e.g. `$dto->supplier->address`, `$dto->items[0]->totalAmount`). 

You can also convert it to a plain array to pass into a Blade view:

```php
$arrayData = $dto->toArray();

return view('gst-invoice::sample-invoice', ['invoice' => $arrayData]);
```

## Creating a PDF

Since you have a clean Blade view, you can use any PDF generator package to render it:

```php
// Example using barryvdh/laravel-dompdf
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('invoices.template', ['invoice' => $invoice->toStructuredData()->toArray()]);

return $pdf->download('invoice.pdf');
```

## The JSON Snapshot

Because an invoice represents a legally binding financial document, its properties (like supplier name or unit price) should never change if the underlying system models change. 

When an invoice is generated, the `toStructuredData()` representation is automatically cached into the `billing_details` JSON column on the `gst_invoices` table. This acts as an immutable historical snapshot ensuring that if you reprint the PDF 5 years later, it looks exactly as it did on the day it was issued.

---
[← Back to Documentation Index](../README.md)
