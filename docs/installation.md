# Installation & Setup

[← Back to Documentation Index](README.md)

This guide covers the installation and initial setup of the Laravel GST Invoice package.

## Requirements

- PHP 8.1+
- Laravel 10.0+ / 11.0+

## 1. Install the Package

Install the package into your Laravel application via Composer:

```bash
composer require anjan-talukdar/laravel-gst-invoice
```

## 2. Publish Assets

Publish the package configuration file and database migrations:

```bash
php artisan vendor:publish --tag="gst-invoice-config"
php artisan vendor:publish --tag="gst-invoice-migrations"
```

This will create:
- `config/gst-invoice.php`
- `database/migrations/xxxx_xx_xx_xxxxxx_create_gst_invoices_table.php`
- `database/migrations/xxxx_xx_xx_xxxxxx_create_gst_invoice_items_table.php`
- `database/migrations/xxxx_xx_xx_xxxxxx_create_invoice_number_sequences_table.php`

## 3. Run Migrations

Apply the migrations to create the necessary tables in your database:

```bash
php artisan migrate
```

## 4. Sync Initial Number Sequences

The package maintains a separate `invoice_number_sequences` table to guarantee atomic and collision-free invoice numbering across financial years.

Whenever you change prefix settings in your `config/gst-invoice.php`, you should run the following artisan command to synchronize those prefixes into the sequence database:

```bash
php artisan gst-invoice:sync sequences
```

> [!TIP]
> You can safely run this command in your deployment script (e.g., inside Envoyer, Forge, or CI/CD pipelines) after `php artisan migrate`.

## Next Steps

Now that the package is installed, head over to the [Configuration Guide](configuration.md) to set up your supplier details, default prefixes, and validation rules.

---
[← Back to Documentation Index](README.md)
