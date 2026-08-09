# Configuration Reference

[← Back to Documentation Index](README.md)

After publishing the configuration via `php artisan vendor:publish`, you can find the settings file at `config/gst-invoice.php`.

All configuration values act as defaults and can be dynamically overridden per invoice using the `InvoiceOptions` object at runtime.

## Supplier Details

Define the default supplier (your company) details. This data is snapshotted into the invoice record upon creation.

```php
'supplier' => [
    'name' => env('GST_SUPPLIER_NAME', 'Software Provider'),
    'trade_name' => env('GST_SUPPLIER_TRADE_NAME', null),
    'gstin' => env('GST_SUPPLIER_GSTIN', '18AABCL1234F1Z5'),
    'pan' => env('GST_SUPPLIER_PAN', 'AABCL1234F'),
    'address' => env('GST_SUPPLIER_ADDRESS', 'GS Road'),
    'city' => env('GST_SUPPLIER_CITY', 'Guwahati'),
    'state' => env('GST_SUPPLIER_STATE', 'Assam'),
    'state_code' => env('GST_SUPPLIER_STATE_CODE', '18'),
    'pincode' => env('GST_SUPPLIER_PINCODE', '781005'),
    'email' => env('GST_SUPPLIER_EMAIL', 'billing@example.com'),
    'phone' => env('GST_SUPPLIER_PHONE', '9876543210'),
    
    'bank_details' => [
        'bank_name' => env('GST_BANK_NAME', 'HDFC Bank'),
        'account_holder' => env('GST_BANK_ACCOUNT_HOLDER', 'Software Provider'),
        'account_number' => env('GST_BANK_ACCOUNT_NUMBER', '50200012345678'),
        'ifsc' => env('GST_BANK_IFSC', 'HDFC0001234'),
        'branch' => env('GST_BANK_BRANCH', 'Main Branch'),
    ],
],
```

## Document Prefixes

Set the default prefixes for each document type. 
*Note: Make sure to run `php artisan gst-invoice:sync sequences` if you change these.*

```php
'prefixes' => [
    'quotation' => env('GST_PREFIX_QUOTATION', 'QT'),
    'tax_invoice' => env('GST_PREFIX_TAX_INVOICE', 'INV'),
    'credit_note' => env('GST_PREFIX_CREDIT_NOTE', 'CN'),
    'debit_note' => env('GST_PREFIX_DEBIT_NOTE', 'DN'),
    'receipt_voucher' => env('GST_PREFIX_RECEIPT_VOUCHER', 'RV'),
    'bill_of_supply' => env('GST_PREFIX_BILL_OF_SUPPLY', 'BOS'),
    'simple_receipt' => env('GST_PREFIX_SIMPLE_RECEIPT', 'REC'),
],
```

## Default Calculation Strategies

```php
// Padding for the serial number (e.g., 5 means INV/26-27/00001)
'serial_padding' => env('GST_INVOICE_SERIAL_PADDING', 5),

// Default Code Type: HSN (Goods) or SAC (Services)
'default_code_type' => 'SAC',
'default_hsn' => '8471',
'default_sac' => '998313',

// Tax Calculation Mode: 'inclusive' or 'exclusive'
'gst_mode' => env('GST_DEFAULT_MODE', 'inclusive'),

// Odd Paisa Weightage: In odd cent splits (e.g., 100.11), which component gets the extra 1 paisa?
'odd_paisa_weightage' => 'cgst',

// Rounding strategy for the total document amount
// Options: 'standard', 'floor', 'ceil', 'bankers'
'rounding_strategy' => 'standard',
```

## Payment & Due Terms

```php
// Default payment terms (e.g. 'due_on_receipt', 'net_15', 'net_30', 'custom')
'default_payment_terms' => 'due_on_receipt',

// Default number of days before the invoice is due (if specific due_date not provided)
'default_due_days' => 7,
```

## Validation Rules

You can enforce strict input validation for the engine.

```php
'validation' => [
    // Array of strictly allowed GST percentages
    'allowed_gst_rates' => [0, 0.25, 3, 5, 12, 18, 28],
    
    // Validates that GSTIN matches standard regex structure
    'validate_gstin_format' => true,
    
    // Validates HSN (4, 6, 8 digits) and SAC (6 digits) structure
    'validate_hsn_sac_format' => true,
    
    // Reject creating a tax invoice if supplier GSTIN is missing
    'require_supplier_gstin' => false,
    
    // Prevent zero-price line items
    'allow_zero_price_items' => true,
],
```

---
[← Back to Documentation Index](README.md)
