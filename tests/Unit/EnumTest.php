<?php

namespace AnjanTalukdar\GstInvoice\Tests\Unit;

require_once __DIR__ . '/../TestCase.php';

use AnjanTalukdar\GstInvoice\Enums\CodeType;
use AnjanTalukdar\GstInvoice\Enums\IndianState;
use AnjanTalukdar\GstInvoice\Enums\OddPaisaWeightage;
use AnjanTalukdar\GstInvoice\Enums\TaxCategory;
use AnjanTalukdar\GstInvoice\Tests\TestCase;

class EnumTest extends TestCase
{
    public function test_indian_state_enum_lookups(): void
    {
        $assam = IndianState::fromCode('18');
        $this->assertEquals(IndianState::ASSAM, $assam);
        $this->assertEquals('Assam', $assam->nameText());

        $maharashtra = IndianState::fromCode('27');
        $this->assertEquals(IndianState::MAHARASHTRA, $maharashtra);

        $this->assertNull(IndianState::fromCode('999'));
    }

    public function test_code_type_labels(): void
    {
        $this->assertEquals('HSN', CodeType::HSN->value);
        $this->assertEquals('SAC', CodeType::SAC->value);
    }

    public function test_tax_category_and_odd_paisa_enums(): void
    {
        $this->assertEquals('taxable', TaxCategory::TAXABLE->value);
        $this->assertEquals('exempt', TaxCategory::EXEMPT->value);
        $this->assertEquals('cgst', OddPaisaWeightage::CGST->value);
        $this->assertEquals('sgst', OddPaisaWeightage::SGST->value);
    }
}
