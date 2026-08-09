# GST Returns & Compliance Data

[← Back to Documentation Index](../README.md)

Filing monthly GSTR-1 returns requires parsing all issued invoices and organizing them into specific tables based on B2B (Business to Business), B2C (Business to Consumer), Credit/Debit Notes, and Advances.

The `ComplianceService` handles this extraction automatically.

## Extracting GSTR-1 Data

You can fetch the compiled array dataset for a specific month (format `YYYY-MM`):

```php
use AnjanTalukdar\GstInvoice\Facades\GstInvoice;

$gstr1 = GstInvoice::reports()->getGstr1Data(returnPeriod: '2026-08');

// $gstr1['b2b'] contains all invoices where recipient has a GSTIN
// $gstr1['b2cs'] contains small consumer sales
// $gstr1['cdnr'] contains credit/debit notes linked to registered entities
```

## Exporting for the GST Portal

You can export this data directly into JSON or CSV formats ready for accounting software or the GST Offline Utility.

### Export to JSON

```php
$jsonString = GstInvoice::reports()->exportGstr1Json('2026-08');

// Return as a downloadable response
return response()->streamDownload(function () use ($jsonString) {
    echo $jsonString;
}, 'GSTR1_2026_08.json', ['Content-Type' => 'application/json']);
```

### Export to CSV

```php
$csvString = GstInvoice::reports()->exportGstr1Csv('2026-08');

return response()->streamDownload(function () use ($csvString) {
    echo $csvString;
}, 'GSTR1_2026_08.csv', ['Content-Type' => 'text/csv']);
```

---
[← Back to Documentation Index](../README.md)
