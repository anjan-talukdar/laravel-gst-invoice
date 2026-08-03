<?php

namespace AnjanTalukdar\GstInvoice\Enums;

enum IndianState: string
{
    case JAMMU_AND_KASHMIR = '01';
    case HIMACHAL_PRADESH = '02';
    case PUNJAB = '03';
    case CHANDIGARH = '04';
    case UTTARAKHAND = '05';
    case HARYANA = '06';
    case DELHI = '07';
    case RAJASTHAN = '08';
    case UTTAR_PRADESH = '09';
    case BIHAR = '10';
    case SIKKIM = '11';
    case ARUNACHAL_PRADESH = '12';
    case NAGALAND = '13';
    case MANIPUR = '14';
    case MIZORAM = '15';
    case TRIPURA = '16';
    case MEGHALAYA = '17';
    case ASSAM = '18';
    case WEST_BENGAL = '19';
    case JHARKHAND = '20';
    case ODISHA = '21';
    case CHHATTISGARH = '22';
    case MADHYA_PRADESH = '23';
    case GUJARAT = '24';
    case DADRA_AND_NAGAR_HAVELI_AND_DAMAN_AND_DIU = '26';
    case MAHARASHTRA = '27';
    case ANDHRA_PRADESH_OLD = '28';
    case KARNATAKA = '29';
    case GOA = '30';
    case LAKSHADWEEP = '31';
    case KERALA = '32';
    case TAMIL_NADU = '33';
    case PUDUCHERRY = '34';
    case ANDAMAN_AND_NICOBAR_ISLANDS = '35';
    case TELANGANA = '36';
    case ANDHRA_PRADESH = '37';
    case LADAKH = '38';
    case OTHER_TERRITORY = '97';

    public function nameText(): string
    {
        return match ($this) {
            self::JAMMU_AND_KASHMIR => 'Jammu and Kashmir',
            self::HIMACHAL_PRADESH => 'Himachal Pradesh',
            self::PUNJAB => 'Punjab',
            self::CHANDIGARH => 'Chandigarh',
            self::UTTARAKHAND => 'Uttarakhand',
            self::HARYANA => 'Haryana',
            self::DELHI => 'Delhi',
            self::RAJASTHAN => 'Rajasthan',
            self::UTTAR_PRADESH => 'Uttar Pradesh',
            self::BIHAR => 'Bihar',
            self::SIKKIM => 'Sikkim',
            self::ARUNACHAL_PRADESH => 'Arunachal Pradesh',
            self::NAGALAND => 'Nagaland',
            self::MANIPUR => 'Manipur',
            self::MIZORAM => 'Mizoram',
            self::TRIPURA => 'Tripura',
            self::MEGHALAYA => 'Meghalaya',
            self::ASSAM => 'Assam',
            self::WEST_BENGAL => 'West Bengal',
            self::JHARKHAND => 'Jharkhand',
            self::ODISHA => 'Odisha',
            self::CHHATTISGARH => 'Chhattisgarh',
            self::MADHYA_PRADESH => 'Madhya Pradesh',
            self::GUJARAT => 'Gujarat',
            self::DADRA_AND_NAGAR_HAVELI_AND_DAMAN_AND_DIU => 'Dadra and Nagar Haveli and Daman and Diu',
            self::MAHARASHTRA => 'Maharashtra',
            self::ANDHRA_PRADESH_OLD => 'Andhra Pradesh (Old)',
            self::KARNATAKA => 'Karnataka',
            self::GOA => 'Goa',
            self::LAKSHADWEEP => 'Lakshadweep',
            self::KERALA => 'Kerala',
            self::TAMIL_NADU => 'Tamil Nadu',
            self::PUDUCHERRY => 'Puducherry',
            self::ANDAMAN_AND_NICOBAR_ISLANDS => 'Andaman and Nicobar Islands',
            self::TELANGANA => 'Telangana',
            self::ANDHRA_PRADESH => 'Andhra Pradesh',
            self::LADAKH => 'Ladakh',
            self::OTHER_TERRITORY => 'Other Territory',
        };
    }

    public static function fromCode(?string $code): ?self
    {
        if (empty($code)) {
            return null;
        }

        $code = str_pad(trim($code), 2, '0', STR_PAD_LEFT);
        return self::tryFrom($code);
    }
}
