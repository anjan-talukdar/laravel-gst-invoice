<?php

namespace AnjanTalukdar\GstInvoice\Data;

use JsonSerializable;

class PartySnapshotData implements JsonSerializable
{
    public function __construct(
        public string $name,
        public ?string $tradeName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $gstin = null,
        public ?string $pan = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $stateName = null,
        public ?string $stateCode = null,
        public ?string $pincode = null,
        public ?array $bankDetails = null,
        public ?string $shippingAddress = null,
        public ?string $shippingCity = null,
        public ?string $shippingStateName = null,
        public ?string $shippingStateCode = null,
        public ?string $shippingPincode = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            tradeName: $data['trade_name'] ?? ($data['tradeName'] ?? null),
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            gstin: $data['gstin'] ?? null,
            pan: $data['pan'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            stateName: $data['state_name'] ?? ($data['state'] ?? null),
            stateCode: $data['state_code'] ?? null,
            pincode: $data['pincode'] ?? null,
            bankDetails: $data['bank_details'] ?? null,
            shippingAddress: $data['shipping_address'] ?? ($data['shippingAddress'] ?? null),
            shippingCity: $data['shipping_city'] ?? ($data['shippingCity'] ?? null),
            shippingStateName: $data['shipping_state_name'] ?? ($data['shippingStateName'] ?? null),
            shippingStateCode: $data['shipping_state_code'] ?? ($data['shippingStateCode'] ?? null),
            shippingPincode: $data['shipping_pincode'] ?? ($data['shippingPincode'] ?? null)
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'trade_name' => $this->tradeName,
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
            'shipping_address' => $this->shippingAddress,
            'shipping_city' => $this->shippingCity,
            'shipping_state_name' => $this->shippingStateName,
            'shipping_state_code' => $this->shippingStateCode,
            'shipping_pincode' => $this->shippingPincode,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
