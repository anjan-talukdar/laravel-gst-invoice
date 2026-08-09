# GST Invoice Rules

[← Back to Documentation Index](../README.md)

This package implements the requirements of **CGST Rules 46 to 55**, which dictate the mandatory fields and formatting of a Tax Invoice in India.

## Mandatory Fields (Rule 46)

The package's calculation engine and database schema natively support capturing all statutory fields:

- **Name, address, and GSTIN of the supplier**
- **Consecutive serial number** not exceeding 16 characters (handled by our Sequence generator)
- **Date of its issue**
- **Name, address, and GSTIN/UIN of the recipient** (if registered)
- **HSN code of goods or SAC for services**
- **Description of goods or services**
- **Quantity in case of goods and unit or Unique Quantity Code thereof**
- **Total value of supply**
- **Taxable value of supply** considering discount or abatement
- **Rate of tax** (CGST, SGST, IGST, UTGST or cess)
- **Amount of tax charged**
- **Place of supply** along with the name of State and its code (handled by our POS engine)
- **Address of delivery** where the same is different from the place of supply
- Whether the tax is payable on **reverse charge basis**

## Time of Supply (Section 31)

You must ensure that your application logic calls `GstInvoice::taxInvoice()->create()` at the correct statutory time:
- **For Goods**: At or before the time of removal of goods.
- **For Services**: Within 30 days from the date of provision of service.

*(The package does not enforce these timelines, as it cannot know when you actually delivered the goods. It is your application's responsibility to generate the invoice promptly.)*

---
[← Back to Documentation Index](../README.md)
