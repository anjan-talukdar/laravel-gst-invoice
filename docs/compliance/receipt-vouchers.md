# Receipt Vouchers Compliance

[← Back to Documentation Index](../README.md)

Advance payments trigger GST liability, requiring the issuance of a Receipt Voucher.

## Section 31(3)(d) of CGST Act

A registered person shall, on receipt of advance payment with respect to any supply of goods or services, issue a receipt voucher or any other document, evidencing receipt of such payment.

*Note: Relief is generally provided for advances received against goods (notification No. 66/2017-Central Tax), meaning you generally only need to pay GST on advances for **services**, not goods.*

## Mandatory Fields for Receipt Vouchers

Our `ReceiptVoucherService` automatically ensures these fields are populated:
- Name, address, and GSTIN of supplier
- Consecutive serial number
- Date of issue
- Name, address, and GSTIN/UIN of recipient
- Description of goods or services
- Amount of advance taken
- Rate of tax and amount of tax charged
- Place of supply
- Whether tax is payable on reverse charge

## Refund Vouchers

If an advance is received (and a Receipt Voucher issued), but subsequently no supply is made and no tax invoice is issued, the supplier must issue a **Refund Voucher** against the advance.

*(Presently, you can handle this by issuing a Credit Note against the Receipt Voucher, or issuing a separate simple receipt for the refund, depending on your accountant's specific filing strategy).*

---
[← Back to Documentation Index](../README.md)
