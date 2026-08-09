# Upgrading Guide

## Upgrading to the latest version

### 1. Database Migrations

Run new migrations to apply schema updates:

```bash
php artisan migrate
```

### 2. Prefix Settings and Sequences

A new table `invoice_number_sequences` was introduced. Run the following command to sync your config prefixes into the database:

```bash
php artisan gst-invoice:sync sequences
```

### 3. Payment Models Decoupled

If you were relying on internal package payment models in an older private version, please note they have been removed in favor of `gst_invoices.paid_amount` / `gst_invoices.due_amount` and Domain Events (`InvoicePaid`, `InvoicePaymentStatusChanged`). 
Your application should manage its own payment gateway and ledger tables by listening to these events.
