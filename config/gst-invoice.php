<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Supplier Profile Snapshot
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Default Prefix Configurations per Document Type
    |--------------------------------------------------------------------------
    */
    'prefix' => env('GST_INVOICE_PREFIX', 'INV'),
    'prefixes' => [
        'quotation' => env('GST_PREFIX_QUOTATION', 'QT'),
        'tax_invoice' => env('GST_PREFIX_TAX_INVOICE', 'INV'),
        'credit_note' => env('GST_PREFIX_CREDIT_NOTE', 'CN'),
        'debit_note' => env('GST_PREFIX_DEBIT_NOTE', 'DN'),
        'receipt_voucher' => env('GST_PREFIX_RECEIPT_VOUCHER', 'RV'),
        'bill_of_supply' => env('GST_PREFIX_BILL_OF_SUPPLY', 'BOS'),
        'simple_receipt' => env('GST_PREFIX_SIMPLE_RECEIPT', 'REC'),
    ],

    'serial_padding' => env('GST_INVOICE_SERIAL_PADDING', 5),

    'default_code_type' => 'SAC',
    'default_hsn' => '8471',
    'default_sac' => '998313',
    'default_gst_rate' => 18.00,
    'default_tax_category' => 'taxable',

    'gst_mode' => env('GST_DEFAULT_MODE', 'inclusive'),

    'currency_symbol' => '₹',
    'currency_code' => 'INR',

    'default_payment_terms' => 'due_on_receipt',
    'default_due_days' => 7,

    'rounding_strategy' => 'standard',
    'odd_paisa_weightage' => 'cgst',

    'validation' => [
        'allowed_gst_rates' => [0, 0.25, 3, 5, 12, 18, 28],
        'validate_gstin_format' => true,
        'validate_hsn_sac_format' => true,
        'require_supplier_gstin' => false,
        'require_recipient_address' => false,
        'allow_zero_price_items' => true,
        'max_items_per_invoice' => 500,
    ],
];
