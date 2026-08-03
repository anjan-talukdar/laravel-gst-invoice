# Laravel GST Invoice

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anjan-talukdar/laravel-gst-invoice.svg?style=flat-square)](https://packagist.org/packages/anjan-talukdar/laravel-gst-invoice)
[![Total Downloads](https://img.shields.io/packagist/dt/anjan-talukdar/laravel-gst-invoice.svg?style=flat-square)](https://packagist.org/packages/anjan-talukdar/laravel-gst-invoice)
[![License](https://img.shields.io/packagist/l/anjan-talukdar/laravel-gst-invoice.svg?style=flat-square)](LICENSE)

A production-ready, lightweight, type-safe, and highly extensible **GST Invoicing & Calculation Engine** for Laravel applications. Designed specifically for e-commerce, SaaS, digital products, subscriptions, retail, and service businesses in India.

> [!NOTE]
> **Scope Notice**: This package is a dedicated **Invoice Generation & Calculation Engine**. It does not handle GST return filing (GSTR-1/3B), Government e-Invoicing IRN APIs, or accounting ledgers, keeping the codebase lightweight, fast, and modular.

---

## Table of Contents

- [Key Features](#key-features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start & Usage Guide](#quick-start--usage-guide)
  - [1. Standalone Checkout Calculation (No DB Write)](#1-standalone-checkout-calculation-no-db-write)
  - [2. Invoice Generation](#2-invoice-generation)
  - [3. Recording Payment](#3-recording-payment)
  - [4. Cancelling an Invoice](#4-cancelling-an-invoice)
- [API Reference & Parameter Tables](#api-reference--parameter-tables)
  - [1. Line Item Structure (`$items[]`)](#1-line-item-structure-items)
  - [2. Options Parameter (`$options`)](#2-options-parameter-options)
  - [3. Recipient Parameter (`$recipient`)](#3-recipient-parameter-recipient)
  - [4. Payment Recording Parameters](#4-payment-recording-parameters)
  - [5. Cancellation Parameters](#5-cancellation-parameters)
- [Advanced Customization](#advanced-customization)
  - [Custom Invoice Number Generator](#custom-invoice-number-generator)
- [Domain Events](#domain-events)
- [Rendering PDFs & Blade Views](#rendering-pdfs--blade-views)
- [Enums Reference](#enums-reference)
- [Testing](#testing)
- [License](#license)

---

## Key Features

- **Goods (HSN) & Services (SAC) Support**: Handles both physical goods (HSN) and services (SAC) line items.
- **Standalone Checkout Calculation Engine**: Run real-time tax math, discount allocations, and GST slab summaries for checkout pages, shopping carts, and quotations **without creating database records**.
- **Place of Supply (POS) Engine**: Automatically determines **Intra-State** (`CGST` + `SGST`) vs **Inter-State** (`IGST`) tax routing based on 2-digit Indian State Codes (`01`–`38`).
- **Reverse Charge Mechanism (RCM)**: Full support for reverse charge invoices.
- **Line-Item Tax Categories**: Classify line items as `Taxable`, `Exempt`, `Nil Rated`, or `Non-GST`.
- **Odd Paisa Tax Weightage**: Odd paisa tax splits (e.g. ₹100.11 tax) allocate the remainder 1 paisa to `CGST` or `SGST` based on configuration, ensuring `CGST + SGST === Total GST` down to the exact paisa.
- **Strict Financial Immutability**: Invoices block editing of financial attributes once created, maintaining accounting integrity.
- **Normalized Tables + JSON Snapshot**: Persists relational `gst_invoice_items` for queries & analytics while maintaining a fast, cached `billing_details` JSON rendering snapshot (`schema_version: "1.0"`).
- **Interface-Driven Architecture**: Decoupled `InvoiceNumberGeneratorInterface` (supports custom patterns like `INV/25-26/00001` or `BBZ-2026-001`) and `TaxCalculatorInterface`.
- **Money Value Object (`Money`)**: Internal precision math powered by a custom `Money` Value Object.
- **PHP 8.1+ Enums**: 11 strongly-typed Enums covering all statuses, payment modes, terms, rounding strategies, and Indian State codes.
- **Domain Event Ecosystem**: 12 lifecycle events (`InvoiceCreating`, `InvoiceCreated`, `InvoicePaid`, `InvoiceCancelled`, etc.) for webhooks and notifications.
- **PDF & Rendering Agnostic**: Returns a structured `InvoiceData` DTO ready for any PDF generator (DomPDF, Browsershot, Snappy) or Blade/React/Vue view.

---

## Installation

Install the package via Composer:

```bash
composer require anjan-talukdar/laravel-gst-invoice
```

Publish the configuration file and database migrations:

```bash
php artisan vendor:publish --tag="gst-invoice-config"
php artisan vendor:publish --tag="gst-invoice-migrations"
```

Run the database migrations:

```bash
php artisan migrate
```

---

## Configuration

The published configuration file is located at `config/gst-invoice.php`. All configuration values act as default fallbacks and can be dynamically overridden at runtime per invoice or calculation call.

```php
return [
    'supplier' => [
        'name' => env('GST_SUPPLIER_NAME', 'Software Provider'),
        'gstin' => env('GST_SUPPLIER_GSTIN', '18AABCL1234F1Z5'),
        'pan' => env('GST_SUPPLIER_PAN', 'AABCL1234F'),
        'address' => 'GS Road',
        'city' => 'Guwahati',
        'state' => 'Assam',
        'state_code' => '18',
        'pincode' => '781005',
        'email' => 'billing@example.com',
        'phone' => '9876543210',
        'bank_details' => [
            'bank_name' => 'HDFC Bank',
            'account_holder' => 'Software Provider',
            'account_number' => '50200012345678',
            'ifsc' => 'HDFC0001234',
            'branch' => 'Main Branch',
        ],
    ],

    'prefix' => 'INV',
    'serial_padding' => 5,
    'default_code_type' => 'SAC',
    'default_hsn' => '8471',
    'default_sac' => '998313',
    'default_gst_rate' => 18.00,
    'default_tax_category' => 'taxable',
    'gst_mode' => 'inclusive',
    'currency_symbol' => '₹',
    'currency_code' => 'INR',
    'default_payment_terms' => 'due_on_receipt',
    'default_due_days' => 7,
    'default_payment_mode' => 'bank_transfer',
    'rounding_strategy' => 'standard',
    'odd_paisa_weightage' => 'cgst',

    'validation' => [
        'allowed_gst_rates' => [0, 0.25, 3, 5, 12, 18, 28],
        'validate_gstin_format' => true,
        'validate_hsn_sac_format' => true,
        'require_supplier_gstin' => false,
        'require_recipient_address' => false,
        'allow_zero_price_items' => true,
        'max_items_per_invoice' => 500,
    ],
];
```

---

## Quick Start & Usage Guide

### 1. Standalone Checkout Calculation (No DB Write)

Calculate taxes, discount allocations, and GST slabs for checkout pages or quotations without saving anything to the database:

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$items = [
    [
        'description' => 'SaaS Subscription - Pro Plan',
        'code_type' => 'SAC',
        'code' => '998313',
        'quantity' => 1,
        'unit_price' => 5000.00,
        'gst_rate' => 18,
    ],
];

// Returns BillingSummaryData DTO with item breakdowns, discount allocations, & GST slabs
$summary = GstInvoice::calculateSummary($items, [
    'supplier_state_code' => '18', // Assam
    'pos_state_code' => '27',      // Maharashtra (Inter-State IGST)
    'gst_mode' => 'exclusive',
    'discount' => 500.00,          // Bill discount
]);

echo $summary->summary->subtotal;   // 4500.00
echo $summary->summary->igstAmount; // 810.00
echo $summary->summary->total;      // 5310.00
```

### 2. Invoice Generation

Create a fully persisted, immutable GST Invoice with normalized items and JSON snapshot:

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;
use AnjanTalukdar\GstInvoice\Enums\PaymentTerm;
use AnjanTalukdar\GstInvoice\Enums\PaymentMode;

$recipient = [
    'name' => 'Acme Technologies Ltd',
    'email' => 'accounts@acme.com',
    'phone' => '9876543210',
    'gstin' => '27AAACA123411ZS',
    'address' => 'Nariman Point',
    'city' => 'Mumbai',
    'state_name' => 'Maharashtra',
    'state_code' => '27',
    'pincode' => '400021',
];

$items = [
    [
        'description' => 'Custom Software Engineering Services',
        'code_type' => 'SAC',
        'code' => '998313',
        'quantity' => 40,
        'unit' => 'Hours',
        'unit_price' => 1500.00,
        'gst_rate' => 18,
    ],
    [
        'description' => 'Server Hardware Component',
        'code_type' => 'HSN',
        'code' => '8471',
        'tax_category' => 'taxable',
        'quantity' => 1,
        'unit' => 'Pcs',
        'unit_price' => 25000.00,
        'gst_rate' => 18,
    ],
];

$invoice = GstInvoice::createInvoice($recipient, $items, [
    'payment_terms' => PaymentTerm::NET_30->value,
    'payment_mode' => PaymentMode::BANK_TRANSFER->value,
    'is_reverse_charge' => false,
    'remark' => 'Thank you for your business.',
]);

echo $invoice->invoice_number; // e.g. "INV/25-26/00001"
echo $invoice->total_in_words; // "Rupees One Lakh Hundred..."
```

### 3. Recording Payment

Mark an invoice as paid or partially paid:

```php
// Mark invoice as fully paid
GstInvoice::markAsPaid($invoice, [
    'amount' => $invoice->total,
    'paid_at' => now(),
]);

echo $invoice->payment_status->value; // 'paid'
```

### 4. Cancelling an Invoice

Cancel an invoice with an audit reason and record who performed the cancellation:

```php
GstInvoice::cancelInvoice($invoice, 'Duplicate invoice created by mistake', auth()->id());

echo $invoice->status->value;              // 'cancelled'
echo $invoice->cancellation_reason;       // 'Duplicate invoice created by mistake'
```

---

## API Reference & Parameter Tables

### 1. Line Item Structure (`$items[]`)

Line item array format passed to `calculateSummary()` and `createInvoice()`:

| Parameter | Data Type | Requirement | Default Value | Options / Enum Values | Description |
|---|---|---|---|---|---|
| `description` | `string` | **Mandatory** | - | Any string | Product or service description |
| `unit_price` | `float` / `int` | **Mandatory** | `0.00` | Numeric (>= 0) | Unit price before or inclusive of GST |
| `quantity` | `float` / `int` | Optional | `1.0` | Numeric (> 0) | Quantity of units |
| `unit` | `string` | Optional | `'Pcs'` | Any string (e.g. `Pcs`, `Nos`, `Hours`, `Service`, `Kg`) | Unit of measure |
| `code_type` | `string` / `CodeType` | Optional | `'SAC'` | `'HSN'`, `'SAC'`, `CodeType::HSN`, `CodeType::SAC` | Code classification (Goods vs Services) |
| `code` | `string` | Optional | `'998313'` | HSN (4, 6, 8 digits) or SAC (6 digits) | HSN or SAC code number |
| `tax_category` | `string` / `TaxCategory` | Optional | `'taxable'` | `'taxable'`, `'exempt'`, `'nil_rated'`, `'non_gst'` | Tax category classification |
| `gst_rate` | `float` / `int` | Optional | `18.0` | `0`, `0.25`, `3`, `5`, `12`, `18`, `28` | GST percentage tax rate |
| `discount` / `item_discount` | `float` / `int` | Optional | `0.00` | Numeric (>= 0) | Direct item-level discount amount |
| `sort_order` | `int` | Optional | `0` | Integer | Line item display sort order |
| `meta_data` | `array` | Optional | `null` | Key-value array | Additional item metadata |

---

### 2. Options Parameter (`$options`)

Calculation and invoice generation options array:

| Option Key | Data Type | Requirement | Default Value | Allowed Options / Enum Values | Description |
|---|---|---|---|---|---|
| `gst_mode` | `string` / `GstMode` | Optional | config default (`'inclusive'`) | `'inclusive'`, `'exclusive'`, `GstMode::INCLUSIVE`, `GstMode::EXCLUSIVE` | Tax calculation mode |
| `discount` | `float` / `int` | Optional | `0.00` | Numeric (>= 0) | Total bill-level discount to allocate proportionally |
| `discount_mode` | `string` / `DiscountMode` | Optional | `'bill'` | `'bill'`, `'item'`, `DiscountMode::BILL`, `DiscountMode::ITEM` | Discount strategy mode |
| `supplier_state_code` | `string` | Optional | config default (`'18'`) | 2-digit Indian State Code (`01`–`38`, `97`) | Supplier state code for POS check |
| `pos_state_code` | `string` / `IndianState` | Optional | Recipient state code | 2-digit Indian State Code (`01`–`38`, `97`), `IndianState` Enum | Place of Supply state code for IGST vs CGST+SGST |
| `pos_state_name` | `string` | Optional | Recipient state name | Any state name string (e.g. `'Maharashtra'`) | Place of Supply state name |
| `is_interstate` | `bool` | Optional | Auto-calculated | `true`, `false` | Explicitly override IGST vs CGST+SGST determination |
| `is_reverse_charge` | `bool` | Optional | `false` | `true`, `false` | Enable Reverse Charge Mechanism (RCM) |
| `rounding_strategy` | `string` / `RoundingStrategy` | Optional | config default (`'standard'`) | `'standard'`, `'floor'`, `'ceil'`, `'bankers'`, `RoundingStrategy` Enum | Rounding strategy algorithm |
| `odd_paisa_weightage` | `string` / `OddPaisaWeightage` | Optional | config default (`'cgst'`) | `'cgst'`, `'sgst'`, `OddPaisaWeightage::CGST`, `OddPaisaWeightage::SGST` | Tax component receiving extra 1 paisa for odd tax splits |
| `invoice_number` | `string` | Optional | Auto-generated | Any unique string | Explicit custom invoice number override |
| `invoice_date` | `DateTimeInterface` / `string` | Optional | `now()` | `YYYY-MM-DD` string or DateTime object | Invoice issue date |
| `due_days` | `int` | Optional | `7` | Integer (> 0) | Days until payment due date |
| `due_date` | `DateTimeInterface` / `string` | Optional | `invoice_date + due_days` | `YYYY-MM-DD` string or DateTime object | Explicit payment due date |
| `payment_terms` | `string` / `PaymentTerm` | Optional | config default (`'due_on_receipt'`) | `'due_on_receipt'`, `'net_15'`, `'net_30'`, `'net_60'`, `'custom'`, `PaymentTerm` Enum | Invoice payment terms |
| `payment_mode` | `string` / `PaymentMode` | Optional | config default (`'bank_transfer'`) | `'cash'`, `'upi'`, `'bank_transfer'`, `'card'`, `'cheque'`, `'net_banking'`, `'other'`, `PaymentMode` Enum | Payment method |
| `currency` | `string` | Optional | config default (`'INR'`) | Currency code string (e.g. `'INR'`) | Invoice currency |
| `remark` | `string` | Optional | `null` | Any string | Internal or public remark note |
| `created_by` | `int` / `string` | Optional | `auth()->id()` | Integer ID or string | Identifier of invoice creator |
| `invoicable` | `Model` | Optional | `null` | Eloquent Model | Polymorphic billable entity (Order, Subscription, etc.) |
| `supplier` | `array` | Optional | config default supplier | Array containing `name`, `gstin`, `pan`, `address`, `city`, `state_name`, `state_code`, `pincode`, `bank_details` | Override supplier snapshot information |
| `recipient` | `array` | Optional | From `$recipient` parameter | Array containing `name`, `email`, `phone`, `gstin`, `pan`, `address`, `city`, `state_name`, `state_code`, `pincode` | Override recipient snapshot information |

---

### 3. Recipient Parameter (`$recipient`)

Passed as the first argument to `createInvoice($recipient, $items, $options)`:

| Format Type | Requirement | Structure / Details |
|---|---|---|
| `array` | Optional if in `$options['recipient']` | `['name' => 'Acme Corp', 'email' => 'accounts@acme.com', 'phone' => '9876543210', 'gstin' => '18AABCL1234F1Z5', 'address' => 'GS Road', 'city' => 'Guwahati', 'state_name' => 'Assam', 'state_code' => '18', 'pincode' => '781005']` |
| `GstRecipientInterface` | Optional | Any class implementing `GstRecipientInterface` (`getGstBillingName()`, `getGstBillingGstin()`, etc.) |
| `Model` | Optional | Eloquent Model (e.g. `User`, `Contact`, `Customer`) with `billing_name`, `gstin`, `billing_address`, etc. |

---

### 4. Payment Recording Parameters

Passed to `markAsPaid($invoice, $paymentData)`:

| Parameter Key | Data Type | Requirement | Default Value | Description |
|---|---|---|---|---|
| `$invoice` | `GstInvoice` | **Mandatory** | - | `GstInvoice` model instance |
| `$paymentData['amount']` | `float` / `int` | Optional | `$invoice->total` | Amount paid (supports partial or full payment) |
| `$paymentData['paid_at']` | `DateTimeInterface` / `string` | Optional | `now()` | Date and time when payment was received |

---

### 5. Cancellation Parameters

Passed to `cancelInvoice($invoice, $reason, $cancelledBy)`:

| Argument | Data Type | Requirement | Default Value | Description |
|---|---|---|---|---|
| `$invoice` | `GstInvoice` | **Mandatory** | - | `GstInvoice` model instance to cancel |
| `$reason` | `string` | Optional | `'Cancelled by user'` | Cancellation audit reason |
| `$cancelledBy` | `string` / `int` | Optional | `auth()->id()` | Identifier of user/system performing cancellation |

---

## Advanced Customization

### Custom Invoice Number Generator

Replace the default sequential FY generator (`INV/25-26/00001`) with your custom numbering strategy by binding `InvoiceNumberGeneratorInterface` in your `AppServiceProvider`:

```php
use AnjanTalukdar\GstInvoice\Contracts\InvoiceNumberGeneratorInterface;

$this->app->bind(InvoiceNumberGeneratorInterface::class, function () {
    return new class implements InvoiceNumberGeneratorInterface {
        public function generate(\DateTimeInterface $date, array $options = []): string
        {
            return 'BBZ-' . date('Y') . '-' . str_pad(rand(1, 9999), 5, '0', STR_PAD_LEFT);
        }
    };
});
```

---

## Domain Events

The package dispatches 12 domain lifecycle events that you can listen to in your application (e.g., for sending email notifications, triggering webhooks, or updating order statuses).

### List of Available Events

| Event Class | Trigger Condition | Public Properties / Payload |
|---|---|---|
| `AnjanTalukdar\GstInvoice\Events\InvoiceCreating` | Fired before validating & creating an invoice | `$data` (array: items & recipient), `$options` (array) |
| `AnjanTalukdar\GstInvoice\Events\InvoiceCreated` | Fired after invoice & items are created | `$invoice` (`GstInvoice`) |
| `AnjanTalukdar\GstInvoice\Events\InvoiceUpdating` | Fired before invoice header attributes update | `$invoice` (`GstInvoice`), `$changes` (array) |
| `AnjanTalukdar\GstInvoice\Events\InvoiceUpdated` | Fired after invoice header attributes update | `$invoice` (`GstInvoice`) |
| `AnjanTalukdar\GstInvoice\Events\InvoicePaymentStatusChanging` | Fired before changing invoice payment status | `$invoice` (`GstInvoice`), `$newStatus` (string), `$amount` (float) |
| `AnjanTalukdar\GstInvoice\Events\InvoicePaymentStatusChanged` | Fired after changing invoice payment status | `$invoice` (`GstInvoice`), `$oldStatus` (string), `$newStatus` (string) |
| `AnjanTalukdar\GstInvoice\Events\InvoicePaid` | Fired when invoice becomes fully paid | `$invoice` (`GstInvoice`), `$paymentData` (array) |
| `AnjanTalukdar\GstInvoice\Events\InvoicePartiallyPaid` | Fired when a partial payment is recorded | `$invoice` (`GstInvoice`), `$paidAmount` (float), `$dueAmount` (float) |
| `AnjanTalukdar\GstInvoice\Events\InvoiceOverdue` | Fired when invoice passes due date | `$invoice` (`GstInvoice`) |
| `AnjanTalukdar\GstInvoice\Events\InvoiceCancelling` | Fired before invoice cancellation | `$invoice` (`GstInvoice`), `$reason` (?string) |
| `AnjanTalukdar\GstInvoice\Events\InvoiceCancelled` | Fired after invoice is marked cancelled | `$invoice` (`GstInvoice`), `$reason` (?string), `$cancelledBy` (mixed) |
| `AnjanTalukdar\GstInvoice\Events\InvoiceDeleted` | Fired when an invoice record is deleted | `$invoiceId` (int) |

### Registering Event Listeners

Subscribe to any of these events in your `EventServiceProvider`:

```php
use AnjanTalukdar\GstInvoice\Events\InvoiceCreated;
use AnjanTalukdar\GstInvoice\Events\InvoicePaid;
use AnjanTalukdar\GstInvoice\Events\InvoiceCancelled;

protected $listen = [
    InvoiceCreated::class => [
        SendInvoiceNotification::class,
    ],
    InvoicePaid::class => [
        DispatchOrderProvisioning::class,
    ],
    InvoiceCancelled::class => [
        LogInvoiceCancellationAudit::class,
    ],
];
```

---

## Rendering PDFs & Blade Views

Convert any `GstInvoice` model to a structured DTO for rendering:

```php
$dto = $invoice->toStructuredData();

// Pass to your favorite PDF library (DomPDF, Browsershot, Snappy)
return view('gst-invoice::sample-invoice', ['invoice' => $dto->toArray()]);
```

---

## Enums Reference

The package includes 11 strongly-typed PHP 8.1+ Enums:

- `AnjanTalukdar\GstInvoice\Enums\CodeType` (`HSN`, `SAC`)
- `AnjanTalukdar\GstInvoice\Enums\TaxCategory` (`TAXABLE`, `EXEMPT`, `NIL_RATED`, `NON_GST`)
- `AnjanTalukdar\GstInvoice\Enums\GstMode` (`INCLUSIVE`, `EXCLUSIVE`)
- `AnjanTalukdar\GstInvoice\Enums\InvoiceStatus` (`ACTIVE`, `CANCELLED`)
- `AnjanTalukdar\GstInvoice\Enums\PaymentStatus` (`UNPAID`, `PAID`, `PARTIAL`, `OVERDUE`)
- `AnjanTalukdar\GstInvoice\Enums\PaymentTerm` (`DUE_ON_RECEIPT`, `NET_15`, `NET_30`, `NET_60`, `CUSTOM`)
- `AnjanTalukdar\GstInvoice\Enums\PaymentMode` (`CASH`, `UPI`, `BANK_TRANSFER`, `CARD`, `CHEQUE`, `NET_BANKING`, `OTHER`)
- `AnjanTalukdar\GstInvoice\Enums\RoundingStrategy` (`STANDARD`, `FLOOR`, `CEIL`, `BANKERS`)
- `AnjanTalukdar\GstInvoice\Enums\DiscountMode` (`BILL`, `ITEM`)
- `AnjanTalukdar\GstInvoice\Enums\OddPaisaWeightage` (`CGST`, `SGST`)
- `AnjanTalukdar\GstInvoice\Enums\IndianState` (Full list of 36+ Indian States with 2-digit GST state codes)

---

## Testing

Run the package test suite:

```bash
vendor/bin/phpunit
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
