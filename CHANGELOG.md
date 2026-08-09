# Changelog

All notable changes to `laravel-gst-invoice` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- 7 native document types with unique, atomic number sequencing (`tax_invoice`, `quotation`, `credit_note`, `debit_note`, `receipt_voucher`, `bill_of_supply`, `simple_receipt`).
- Dedicated sub-services for all document types (`$gst->taxInvoice()`, `$gst->quotations()`, etc.).
- Complete E-Invoice Schema v1.1 payload generation and validation (`$gst->eInvoice()`).
- GSTR-1 & GSTR-3B compliance data layer and JSON/CSV exporters (`$gst->reports()`).
- Quotation to Tax Invoice conversion engine with revision tracking.
- Automatic Credit Note remaining adjustable limits validation (`getRemainingAdjustableAmount()`).
- Full Domain Event ecosystem (`InvoicePaid`, `QuotationAccepted`, `AdvanceReceived`, etc.) for hooking into custom application workflows.
- Reverse Charge Mechanism (RCM) support.
- Odd paisa tax weightage distribution logic (automatically corrects floating point precision splits).

### Changed
- Decoupled payment architectures (removed internal package payment models/tables) to ensure maximum flexibility for application-level gateways and ledgers.
- Refactored `README.md` into comprehensive `docs/` structure.
