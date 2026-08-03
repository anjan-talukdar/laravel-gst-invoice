<?php

namespace AnjanTalukdar\GstInvoice\Data;

use JsonSerializable;

class PartySnapshotData implements JsonSerializable
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $gstin = null,
        public ?string $pan = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $stateName = null,
        public ?string $stateCode = null,
        public ?string $pincode = null,
        public ?array $bankDetails = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            gstin: $data['gstin'] ?? null,
            pan: $data['pan'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            stateName: $data['state_name'] ?? ($data['state'] ?? null),
            stateCode: $data['state_code'] ?? null,
            pincode: $data['pincode'] ?? null,
            bankDetails: $data['bank_details'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'address' => $this->address,
            'city' => $this->city,
            'state_name' => $this->stateName,
            'state_code' => $this->stateCode,
            'pincode' => $this->pincode,
            'bank_details' => $this->bankDetails,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
