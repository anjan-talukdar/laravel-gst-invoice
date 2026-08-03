<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - {{ $invoice['invoice_number'] ?? $invoice->invoice_number }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-family: sans-serif; font-size: 11px; line-height: 1.4; color: #333; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        .table th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { background: #e0e0e0; padding: 2px 6px; border-radius: 3px; font-size: 10px; }
        .header-box { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .party-container { display: flex; gap: 15px; margin-bottom: 15px; }
        .party-box { flex: 1; border: 1px solid #ccc; padding: 10px; background: #fafafa; }
    </style>
</head>
<body>
    @php
        $data = is_array($invoice) ? $invoice : $invoice->toArray();
    @endphp

    <div class="header-box">
        <div>
            <h2>TAX INVOICE</h2>
            <p><strong>Invoice No:</strong> {{ $data['invoice_number'] }}</p>
            <p><strong>Invoice Date:</strong> {{ $data['invoice_date'] }}</p>
            @if(!empty($data['due_date']))
                <p><strong>Due Date:</strong> {{ $data['due_date'] }}</p>
            @endif
        </div>
        <div class="text-right">
            <p><strong>Place of Supply:</strong> {{ $data['pos_state_name'] ?? 'N/A' }} ({{ $data['pos_state_code'] ?? '-' }})</p>
            <p><strong>Reverse Charge:</strong> {{ !empty($data['is_reverse_charge']) ? 'YES (Tax Payable Under RCM)' : 'NO' }}</p>
            <p><strong>Payment Terms:</strong> {{ strtoupper($data['payment_terms'] ?? 'Due on Receipt') }}</p>
        </div>
    </div>

    <div class="party-container">
        <div class="party-box">
            <h4>Billed By (Supplier)</h4>
            <strong>{{ $data['supplier']['name'] }}</strong><br>
            {{ $data['supplier']['address'] ?? '' }} {{ $data['supplier']['city'] ?? '' }}<br>
            GSTIN: <strong>{{ $data['supplier']['gstin'] ?: 'N/A' }}</strong> | PAN: {{ $data['supplier']['pan'] ?: 'N/A' }}<br>
            State: {{ $data['supplier']['state_name'] ?? '' }} ({{ $data['supplier']['state_code'] ?? '' }})
        </div>
        <div class="party-box">
            <h4>Billed To (Recipient)</h4>
            <strong>{{ $data['recipient']['name'] }}</strong><br>
            {{ $data['recipient']['address'] ?? '' }} {{ $data['recipient']['city'] ?? '' }}<br>
            GSTIN: <strong>{{ $data['recipient']['gstin'] ?: 'N/A' }}</strong> | PAN: {{ $data['recipient']['pan'] ?: 'N/A' }}<br>
            State: {{ $data['recipient']['state_name'] ?? '' }} ({{ $data['recipient']['state_code'] ?? '' }})
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item & Description</th>
                <th>HSN/SAC</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Discount</th>
                <th>Taxable Value</th>
                @if(empty($data['is_interstate']))
                    <th>CGST</th>
                    <th>SGST</th>
                @else
                    <th>IGST</th>
                @endif
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['items'] as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item['description'] }}</strong>
                        @if(($item['tax_category'] ?? 'taxable') !== 'taxable')
                            <span class="badge">{{ strtoupper($item['tax_category']) }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item['code'] }} ({{ $item['code_type'] }})</td>
                    <td class="text-center">{{ $item['quantity'] }} {{ $item['unit'] ?? '' }}</td>
                    <td class="text-right">₹{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">₹{{ number_format($item['item_discount'] + $item['bill_discount'], 2) }}</td>
                    <td class="text-right">₹{{ number_format($item['taxable_amount'], 2) }}</td>
                    @if(empty($data['is_interstate']))
                        <td class="text-right">₹{{ number_format($item['cgst_amount'], 2) }} ({{ $item['gst_rate'] / 2 }}%)</td>
                        <td class="text-right">₹{{ number_format($item['sgst_amount'], 2) }} ({{ $item['gst_rate'] / 2 }}%)</td>
                    @else
                        <td class="text-right">₹{{ number_format($item['igst_amount'], 2) }} ({{ $item['gst_rate'] }}%)</td>
                    @endif
                    <td class="text-right"><strong>₹{{ number_format($item['total_amount'], 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 15px; display: flex; justify-content: space-between;">
        <div style="width: 50%;">
            <p><strong>Amount in Words:</strong><br>{{ $data['amount_in_words'] }}</p>
            @if(!empty($data['bank_details']))
                <div style="border: 1px solid #ccc; padding: 8px; margin-top: 10px;">
                    <h5 style="margin: 0 0 5px 0;">Bank Details for Payment</h5>
                    Bank: {{ $data['bank_details']['bank_name'] ?? '-' }}<br>
                    Account No: <strong>{{ $data['bank_details']['account_number'] ?? '-' }}</strong><br>
                    IFSC: {{ $data['bank_details']['ifsc'] ?? '-' }} | Branch: {{ $data['bank_details']['branch'] ?? '-' }}
                </div>
            @endif
        </div>
        <div style="width: 45%;">
            <table class="table">
                <tr><td>Gross Taxable Amount</td><td class="text-right">₹{{ number_format($data['summary']['gross_taxable'], 2) }}</td></tr>
                @if($data['summary']['discount'] > 0)
                    <tr><td>Total Discount</td><td class="text-right">-₹{{ number_format($data['summary']['discount'], 2) }}</td></tr>
                @endif
                <tr><td><strong>Net Taxable Value</strong></td><td class="text-right"><strong>₹{{ number_format($data['summary']['subtotal'], 2) }}</strong></td></tr>
                @if(empty($data['is_interstate']))
                    <tr><td>Total CGST</td><td class="text-right">₹{{ number_format($data['summary']['cgst_amount'], 2) }}</td></tr>
                    <tr><td>Total SGST</td><td class="text-right">₹{{ number_format($data['summary']['sgst_amount'], 2) }}</td></tr>
                @else
                    <tr><td>Total IGST</td><td class="text-right">₹{{ number_format($data['summary']['igst_amount'], 2) }}</td></tr>
                @endif
                @if($data['summary']['round_off'] != 0)
                    <tr><td>Round Off</td><td class="text-right">₹{{ number_format($data['summary']['round_off'], 2) }}</td></tr>
                @endif
                <tr style="background:#f5f5f5;"><td><strong>Grand Total</strong></td><td class="text-right"><strong>₹{{ number_format($data['summary']['total'], 2) }}</strong></td></tr>
            </table>
        </div>
    </div>
</body>
</html>
