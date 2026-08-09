# Laravel GST Invoice

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anjan-talukdar/laravel-gst-invoice.svg?style=flat-square)](https://packagist.org/packages/anjan-talukdar/laravel-gst-invoice)
[![Total Downloads](https://img.shields.io/packagist/dt/anjan-talukdar/laravel-gst-invoice.svg?style=flat-square)](https://packagist.org/packages/anjan-talukdar/laravel-gst-invoice)
[![License](https://img.shields.io/packagist/l/anjan-talukdar/laravel-gst-invoice.svg?style=flat-square)](LICENSE)

A production-ready, lightweight, type-safe, and highly extensible **GST Invoicing & Calculation Engine** for Laravel applications. Designed specifically for e-commerce, SaaS, digital products, subscriptions, retail, and service businesses in India.

> [!NOTE]
> **Scope Notice**: This package is a dedicated **Invoice Generation & Calculation Engine**. It does not handle GST return filing (GSTR-1/3B), Government e-Invoicing IRN APIs, or accounting ledgers, keeping the codebase lightweight, fast, and modular.

---

## 📚 Detailed Documentation

The full documentation is organized in the `docs/` directory.

👉 **[Go to Documentation Index](docs/README.md)**

### Quick Links
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Document Types](docs/concepts/document-types.md)
- [GST Calculation & Tax Rules](docs/features/gst-calculation.md)
- [Practical Guides & Tutorials](docs/README.md#practical-guides)

---

## Key Features

- **7 Native Document Types**: Full support for `Tax Invoices`, `Quotations`, `Credit Notes`, `Debit Notes`, `Receipt Vouchers`, `Bills of Supply`, and `Simple Receipts`.
- **Atomic Concurrency-Safe Sequences**: Safe sequence generation per financial year using `lockForUpdate()`.
- **Decoupled Payment Architecture**: Fires rich Domain Events for custom application ledgers while remaining unopinionated about your payment gateways.
- **Compliance Ready**: Outputs structured GSTR-1 datasets and E-Invoice Schema v1.1 payloads.
- **Standalone Calculation Engine**: Run real-time tax math for checkout pages without writing to the database.
- **Place of Supply (POS) Engine**: Automatically routes Intra-State vs Inter-State GST.

---

## Quick Installation

```bash
composer require anjan-talukdar/laravel-gst-invoice

php artisan vendor:publish --tag="gst-invoice-config"
php artisan vendor:publish --tag="gst-invoice-migrations"

php artisan migrate
php artisan gst-invoice:sync sequences
```

[Read the full Installation Guide](docs/installation.md)

---

## Minimal Quick Start Example

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemInput;
use AnjanTalukdar\GstInvoice\Data\RecipientInput;

$recipient = RecipientInput::make('Acme Technologies Ltd')->stateCode('27');
$items = [
    InvoiceItemInput::make('Software Development', 1500.00)->quantity(40)->gstRate(18.0)
];

// Creates a Tax Invoice (INV/26-27/00001)
$taxInvoice = GstInvoice::taxInvoice()->create($recipient, $items);

echo $taxInvoice->invoice_number; // "INV/26-27/00001"
echo $taxInvoice->total;          // "70800.00"
```

For detailed guides, please refer to the [Documentation](docs/README.md).

---

## Testing

Run the package test suite:

```bash
vendor/bin/phpunit packages/anjan-talukdar/laravel-gst-invoice
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
