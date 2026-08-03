# Changelog

All notable changes to `anjan-talukdar/laravel-gst-invoice` will be documented in this file.

## v1.0.0 - 2026-08-03

- Initial release of the GST Invoice Generation & Calculation Engine.
- Support for Goods (HSN) and Services (SAC) line items.
- Standalone Checkout & Quotation calculation engine (`GstInvoice::calculateSummary()`).
- Place of Supply (POS) routing engine for Intra-State vs Inter-State IGST determination.
- Reverse Charge Mechanism (RCM) flag support.
- Line-item Tax Categories (`Taxable`, `Exempt`, `Nil Rated`, `Non-GST`).
- Odd Paisa Tax Split & Weightage logic (`odd_paisa_weightage`: `cgst` or `sgst`).
- Normalized database schema (`gst_invoices` and `gst_invoice_items`) with `DECIMAL(15,2)` precision and performance indexes.
- Immutable JSON rendering snapshot (`billing_details` with `schema_version: "1.0"`).
- Strict Invoice Immutability enforcement (`InvoiceImmutableException`).
- Interface-driven design (`InvoiceNumberGeneratorInterface`, `TaxCalculatorInterface`).
- Lightweight `Money` Value Object for arithmetic precision.
- 11 strongly-typed PHP 8.1+ Enums.
- 12 domain lifecycle events.
