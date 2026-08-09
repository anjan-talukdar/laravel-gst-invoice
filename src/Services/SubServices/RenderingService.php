<?php

namespace AnjanTalukdar\GstInvoice\Services\SubServices;

use AnjanTalukdar\GstInvoice\Models\GstInvoice;

class RenderingService
{
    public function render(GstInvoice $invoice, string $viewName = 'pdf.gst-invoice'): string
    {
        $dto = $invoice->toStructuredData();
        return view($viewName, ['invoice' => $dto->toArray()])->render();
    }
}
