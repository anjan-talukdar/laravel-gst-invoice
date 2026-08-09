# Atomic Invoice Numbering

[← Back to Documentation Index](../README.md)

Generating consecutive, gapless invoice numbers (e.g., `INV/26-27/00001`, `INV/26-27/00002`) is notoriously difficult in high-concurrency web applications because two users checking out at the exact same millisecond might grab the same sequential number, causing a race condition or database unique constraint violation.

## How the Engine Solves This

The package uses a dedicated `invoice_number_sequences` table. When generating an invoice number, it utilizes Laravel's `lockForUpdate()` pessimistic locking mechanism inside a database transaction.

```php
$sequence = InvoiceNumberSequence::where('invoice_type', $type)
    ->where('financial_year', $fyCode)
    ->lockForUpdate() // Locks the row until transaction commits
    ->first();
```

This guarantees that sequence generation is **atomic and thread-safe**. 

## Financial Year Auto-Reset

The sequence automatically resets to `00001` on April 1st of every year, determining the Financial Year (e.g., `26-27`) based on the provided `invoice_date`.

## Custom Number Generators

If you don't want the default `PREFIX/FY/SERIAL` format, you can bind your own implementation of the `InvoiceNumberGeneratorInterface` in your `AppServiceProvider`.

```php
use AnjanTalukdar\GstInvoice\Contracts\InvoiceNumberGeneratorInterface;

$this->app->bind(InvoiceNumberGeneratorInterface::class, function () {
    return new class implements InvoiceNumberGeneratorInterface {
        public function generate(\DateTimeInterface $date, array $options = []): string
        {
            // E.g., BBZ-2026-001
            return 'BBZ-' . $date->format('Y') . '-' . str_pad(rand(1, 9999), 5, '0', STR_PAD_LEFT);
        }
    };
});
```

---
[← Back to Documentation Index](../README.md)
