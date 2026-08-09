# Credit & Debit Notes Compliance

[← Back to Documentation Index](../README.md)

Under Section 34 of the CGST Act, Credit and Debit Notes are strictly regulated.

## Credit Notes (Section 34(1))

A supplier can issue a Credit Note in specific statutory situations:
1. The taxable value or tax charged in the original invoice exceeds the actual taxable value or tax payable.
2. The goods supplied are returned by the recipient.
3. The goods or services supplied are found to be deficient.

**Statutory Requirement**: A Credit Note must explicitly declare the original invoice number against which it is issued. Our `CreditNoteService` enforces this by requiring the original `GstInvoice` model as its first parameter and storing its ID in the `reference_invoice_id` column.

## Debit Notes (Section 34(3))

A supplier must issue a Debit Note if they discover that the taxable value or tax charged in the original invoice is *less* than the actual taxable value or tax payable.

Like Credit Notes, a Debit Note must explicitly reference the original invoice.

## Time Limit for Issuance

GST law mandates a strict time limit for issuing Credit Notes. They must be declared in the GST returns by:
- The 30th of November following the end of the financial year in which such supply was made, OR
- The date of furnishing of the relevant annual return, whichever is earlier.

*(Your application must implement business logic to prevent users from issuing credit notes after this statutory deadline if needed).*

---
[← Back to Documentation Index](../README.md)
