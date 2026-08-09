<?php

namespace AnjanTalukdar\GstInvoice\Commands;

use AnjanTalukdar\GstInvoice\Enums\InvoiceType;
use AnjanTalukdar\GstInvoice\Models\InvoiceNumberSequence;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GstInvoiceSyncCommand extends Command
{
    protected $signature = 'gst-invoice:sync {task=sequences : Task to execute (e.g. sequences)}';

    protected $description = 'Sync GST Invoice master configurations and number sequences with database';

    public function handle(): int
    {
        $task = strtolower((string)$this->argument('task'));

        return match ($task) {
            'sequences' => $this->syncSequences(),
            default => $this->unknownTask($task),
        };
    }

    protected function syncSequences(): int
    {
        $this->info('Syncing invoice number sequences from configuration...');

        $date = now();
        $year = $date->year;
        $month = $date->month;

        if ($month >= 4) {
            $startYear = $year;
            $endYear = $year + 1;
        } else {
            $startYear = $year - 1;
            $endYear = $year;
        }

        $fyCode = substr((string)$startYear, -2) . '-' . substr((string)$endYear, -2);
        $prefixes = config('gst-invoice.prefixes', []);

        foreach (InvoiceType::cases() as $type) {
            $prefix = $prefixes[$type->value] ?? $type->defaultPrefix();

            $sequence = InvoiceNumberSequence::firstOrCreate(
                [
                    'invoice_type' => $type->value,
                    'financial_year' => $fyCode,
                ],
                [
                    'prefix' => $prefix,
                    'last_number' => 0,
                ]
            );

            if ($sequence->prefix !== $prefix) {
                $sequence->update(['prefix' => $prefix]);
            }

            $this->line("  [✓] {$type->label()} ({$type->value}): FY {$fyCode} => Prefix '{$prefix}' (Last #: {$sequence->last_number})");
        }

        $this->info('Invoice number sequences successfully synced!');

        return self::SUCCESS;
    }

    protected function unknownTask(string $task): int
    {
        $this->error("Unknown sync task '{$task}'. Supported tasks: sequences");

        return self::FAILURE;
    }
}
