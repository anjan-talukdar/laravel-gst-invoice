<?php

namespace AnjanTalukdar\GstInvoice\Tests;

use AnjanTalukdar\GstInvoice\GstInvoiceServiceProvider;

if (class_exists(\Orchestra\Testbench\TestCase::class)) {
    abstract class BaseTestCase extends \Orchestra\Testbench\TestCase {}
} elseif (class_exists(\Tests\TestCase::class)) {
    abstract class BaseTestCase extends \Tests\TestCase {}
} else {
    abstract class BaseTestCase extends \Illuminate\Foundation\Testing\TestCase {}
}

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            GstInvoiceServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        $this->app->register(GstInvoiceServiceProvider::class);

        $this->runPackageMigrations();
    }

    protected function runPackageMigrations(): void
    {
        $invoicesMigration = include __DIR__ . '/../database/migrations/create_gst_invoices_table.php.stub';
        $itemsMigration = include __DIR__ . '/../database/migrations/create_gst_invoice_items_table.php.stub';

        $invoicesMigration->up();
        $itemsMigration->up();
    }
}
