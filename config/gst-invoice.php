<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supplier Details (Your Organization)
    |--------------------------------------------------------------------------
    | Default supplier info on GST invoices. Can be overridden per invoice.
    */
    'supplier' => [
        'name' => env('GST_SUPPLIER_NAME', env('APP_NAME', 'Software Provider')),
        'gstin' => env('GST_SUPPLIER_GSTIN', ''),
        'pan' => env('GST_SUPPLIER_PAN', ''),
        'address' => env('GST_SUPPLIER_ADDRESS', ''),
        'city' => env('GST_SUPPLIER_CITY', ''),
        'state' => env('GST_SUPPLIER_STATE', ''),
        'state_code' => env('GST_SUPPLIER_STATE_CODE', ''), // 2-digit state code (e.g. '18')
        'pincode' => env('GST_SUPPLIER_PINCODE', ''),
        'email' => env('GST_SUPPLIER_EMAIL', 'billing@example.com'),
        'phone' => env('GST_SUPPLIER_PHONE', ''),

        'bank_details' => [
            'bank_name' => env('GST_BANK_NAME', ''),
            'account_holder' => env('GST_BANK_ACC_HOLDER', env('APP_NAME', 'Software Provider')),
            'account_number' => env('GST_BANK_ACC_NO', ''),
            'ifsc' => env('GST_BANK_IFSC', ''),
            'branch' => env('GST_BANK_BRANCH', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Defaults & Fallbacks
    |--------------------------------------------------------------------------
    */
    'prefix' => env('GST_INVOICE_PREFIX', 'INV'),
    'serial_padding' => 5,
    'default_code_type' => 'SAC',
    'default_hsn' => env('GST_DEFAULT_HSN', '8471'),
    'default_sac' => env('GST_DEFAULT_SAC', '998313'),
    'default_gst_rate' => (float) env('GST_DEFAULT_RATE', 18.00),
    'default_tax_category' => 'taxable', // taxable, exempt, nil_rated, non_gst
    'gst_mode' => env('GST_MODE', 'inclusive'), // 'inclusive' or 'exclusive'
    'currency_symbol' => '₹',
    'currency_code' => 'INR',

    'default_payment_terms' => 'due_on_receipt', // due_on_receipt, net_15, net_30, net_60, custom
    'default_due_days' => 7,
    'default_payment_mode' => 'bank_transfer', // cash, upi, bank_transfer, card, cheque, net_banking, other

    /*
    |--------------------------------------------------------------------------
    | Rounding Strategy
    |--------------------------------------------------------------------------
    | Strategies: 'standard' (round 2 dec), 'floor', 'ceil', 'bankers'
    | Odd Paisa Weightage: 'cgst' or 'sgst' for odd-paisa tax splits
    */
    'rounding_strategy' => 'standard',
    'odd_paisa_weightage' => 'cgst',

    /*
    |--------------------------------------------------------------------------
    | Business Rule Validation Constraints
    |--------------------------------------------------------------------------
    */
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
