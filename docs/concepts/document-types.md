# Document Types

[← Back to Documentation Index](../README.md)

The Laravel GST Invoice engine supports 7 native statutory document types, mapped to the `AnjanTalukdar\GstInvoice\Enums\InvoiceType` enum.

| Document Type | Enum Value | Default Prefix | Usage Scenario |
|---|---|---|---|
| **Tax Invoice** | `InvoiceType::TAX_INVOICE` | `INV` | Standard B2B or B2C sales of taxable goods/services. |
| **Quotation** | `InvoiceType::QUOTATION` | `QT` | Pro-forma invoices and sales estimates (non-financial until converted). |
| **Credit Note** | `InvoiceType::CREDIT_NOTE` | `CN` | Issued for sales returns, post-sale discounts, or deficiency in services. Reduces tax liability. |
| **Debit Note** | `InvoiceType::DEBIT_NOTE` | `DN` | Issued when the original tax invoice value was lower than the actual taxable value. Increases tax liability. |
| **Receipt Voucher** | `InvoiceType::RECEIPT_VOUCHER` | `RV` | Issued upon receipt of an advance payment before the actual supply of goods/services. |
| **Bill of Supply** | `InvoiceType::BILL_OF_SUPPLY` | `BOS` | Issued for exempt goods/services or by composition scheme dealers (No GST can be charged). |
| **Simple Receipt** | `InvoiceType::SIMPLE_RECEIPT` | `REC` | Non-GST receipt (e.g., standard payment acknowledgments that don't fall under strict GST documentation). |

## Accessing Sub-Services

You interact with each document type using its dedicated sub-service accessible via the `GstInvoice` facade:

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

// Returns TaxInvoiceService
$taxService = GstInvoice::taxInvoice();

// Returns QuotationService
$quoteService = GstInvoice::quotations();

// Returns CreditNoteService
$cnService = GstInvoice::creditNotes();

// Returns DebitNoteService
$dnService = GstInvoice::debitNotes();

// Returns ReceiptVoucherService
$rvService = GstInvoice::receiptVouchers();

// Returns BillOfSupplyService
$bosService = GstInvoice::billsOfSupply();

// Returns SimpleReceiptService
$recService = GstInvoice::simpleReceipts();
```

All sub-services share the core `$service->create($recipient, $items, $options)` method but enforce rules specific to their document type (e.g., Credit Notes require a `referenceInvoiceId`).

---
[← Back to Documentation Index](../README.md)
