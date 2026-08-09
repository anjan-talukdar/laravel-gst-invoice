# GST Returns Filing Compliance

[← Back to Documentation Index](../README.md)

Filing GSTR-1 and GSTR-3B on the GSTN portal requires organizing raw invoice data into specific summarized tables.

## How the Package Helps

The `ComplianceService` handles the heavy lifting of categorizing your data.

### GSTR-1 B2B (Table 4)

This table contains all taxable supplies made to registered persons (businesses with a GSTIN).
The library automatically filters invoices where `recipient_gstin` is not null.

### GSTR-1 B2C Large (Table 5) & Small (Table 7)

This table contains taxable supplies made to unregistered persons (consumers).
- **B2C Large**: Inter-state supplies to unregistered persons where the invoice value is more than ₹2.5 lakhs.
- **B2C Small**: All other intra-state and inter-state supplies to unregistered persons.

### GSTR-1 CDNR (Table 9B)

Credit and Debit Notes issued to registered persons. The library automatically extracts documents of type `credit_note` and `debit_note`.

### GSTR-1 Advances (Table 11A)

Tax liability arising on account of receipt of advance. The library extracts documents of type `receipt_voucher`.

## What Your Application Must Do

While the `ComplianceService` generates the JSON/CSV data arrays required by these tables, you must still upload this data to the GST portal or pass it to an ASP/GSP (Application/GST Suvidha Provider) via their APIs (e.g. ClearTax).

---
[← Back to Documentation Index](../README.md)
