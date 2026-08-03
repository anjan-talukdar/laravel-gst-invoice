<?php

namespace AnjanTalukdar\GstInvoice;

use AnjanTalukdar\GstInvoice\Contracts\InvoiceNumberGeneratorInterface;
use AnjanTalukdar\GstInvoice\Contracts\TaxCalculatorInterface;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceService;
use AnjanTalukdar\GstInvoice\Services\GstInvoiceValidator;
use AnjanTalukdar\GstInvoice\Services\SequentialFyInvoiceNumberGenerator;
use AnjanTalukdar\GstInvoice\Services\TaxCalculator;
use Illuminate\Support\ServiceProvider;

class GstInvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/gst-invoice.php', 'gst-invoice');

        $this->app->bind(TaxCalculatorInterface::class, TaxCalculator::class);
        $this->app->bind(InvoiceNumberGeneratorInterface::class, SequentialFyInvoiceNumberGenerator::class);
        $this->app->singleton(GstInvoiceValidator::class);

        $this->app->singleton('gst-invoice', function ($app) {
            return new GstInvoiceService(
                $app->make(TaxCalculatorInterface::class),
                $app->make(InvoiceNumberGeneratorInterface::class),
                $app->make(GstInvoiceValidator::class)
            );
        });

        $this->app->alias('gst-invoice', GstInvoiceService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/gst-invoice.php' => config_path('gst-invoice.php'),
            ], 'gst-invoice-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/create_gst_invoices_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_gst_invoices_table.php'),
                __DIR__ . '/../database/migrations/create_gst_invoice_items_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_gst_invoice_items_table.php'),
            ], 'gst-invoice-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/gst-invoice'),
            ], 'gst-invoice-views');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'gst-invoice');
    }
}
