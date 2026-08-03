<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum CodeType: string
{
    case HSN = 'HSN';
    case SAC = 'SAC';

    public function label(): string
    {
        return match ($this) {
            self::HSN => 'Harmonized System of Nomenclature (Goods)',
            self::SAC => 'Services Accounting Code (Services)',
        };
    }
}
