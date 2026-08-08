<?php

namespace AnjanTalukdar\GstInvoice\Data;

use AnjanTalukdar\GstInvoice\Enums\IndianState;
use JsonSerializable;

class RecipientInput implements JsonSerializable
{
    public ?string $stateCode = null;
    public ?string $shippingStateCode = null;

    public function __construct(
        public string $name = '',
        public ?string $tradeName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $gstin = null,
        public ?string $pan = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $stateName = null,
        IndianState|string|null $stateCode = null,
        public ?string $pincode = null,
        public ?string $shippingAddress = null,
        public ?string $shippingCity = null,
        public ?string $shippingStateName = null,
        IndianState|string|null $shippingStateCode = null,
        public ?string $shippingPincode = null
    ) {
        $this->stateCode = $this->formatStateCode($stateCode);
        $this->shippingStateCode = $this->formatStateCode($shippingStateCode);
    }

    public static function make(
        string $name = '',
        ?string $tradeName = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $gstin = null,
        ?string $pan = null,
        ?string $address = null,
        ?string $city = null,
        ?string $stateName = null,
        IndianState|string|null $stateCode = null,
        ?string $pincode = null,
        ?string $shippingAddress = null,
        ?string $shippingCity = null,
        ?string $shippingStateName = null,
        IndianState|string|null $shippingStateCode = null,
        ?string $shippingPincode = null
    ): self {
        return new self(
            name: $name,
            tradeName: $tradeName,
            email: $email,
            phone: $phone,
            gstin: $gstin,
            pan: $pan,
            address: $address,
            city: $city,
            stateName: $stateName,
            stateCode: $stateCode,
            pincode: $pincode,
            shippingAddress: $shippingAddress,
            shippingCity: $shippingCity,
            shippingStateName: $shippingStateName,
            shippingStateCode: $shippingStateCode,
            shippingPincode: $shippingPincode
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string)($data['name'] ?? ''),
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
            shippingAddress: $data['shipping_address'] ?? ($data['shippingAddress'] ?? null),
            shippingCity: $data['shipping_city'] ?? ($data['shippingCity'] ?? null),
            shippingStateName: $data['shipping_state_name'] ?? ($data['shippingStateName'] ?? null),
            shippingStateCode: $data['shipping_state_code'] ?? ($data['shippingStateCode'] ?? null),
            shippingPincode: $data['shipping_pincode'] ?? ($data['shippingPincode'] ?? null)
        );
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function tradeName(?string $tradeName): self
    {
        $this->tradeName = $tradeName;
        return $this;
    }

    public function email(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function phone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function gstin(?string $gstin): self
    {
        $this->gstin = $gstin;
        return $this;
    }

    public function pan(?string $pan): self
    {
        $this->pan = $pan;
        return $this;
    }

    public function address(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function city(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function stateName(?string $stateName): self
    {
        $this->stateName = $stateName;
        return $this;
    }

    public function stateCode(IndianState|string|null $stateCode): self
    {
        $this->stateCode = $this->formatStateCode($stateCode);
        return $this;
    }

    public function pincode(?string $pincode): self
    {
        $this->pincode = $pincode;
        return $this;
    }

    public function shippingAddress(?string $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function shippingCity(?string $shippingCity): self
    {
        $this->shippingCity = $shippingCity;
        return $this;
    }

    public function shippingStateName(?string $shippingStateName): self
    {
        $this->shippingStateName = $shippingStateName;
        return $this;
    }

    public function shippingStateCode(IndianState|string|null $shippingStateCode): self
    {
        $this->shippingStateCode = $this->formatStateCode($shippingStateCode);
        return $this;
    }

    public function shippingPincode(?string $shippingPincode): self
    {
        $this->shippingPincode = $shippingPincode;
        return $this;
    }

    protected function formatStateCode(IndianState|string|null $stateCode): ?string
    {
        if ($stateCode instanceof IndianState) {
            return $stateCode->value;
        }

        if ($stateCode !== null && $stateCode !== '') {
            return str_pad((string)$stateCode, 2, '0', STR_PAD_LEFT);
        }

        return null;
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
