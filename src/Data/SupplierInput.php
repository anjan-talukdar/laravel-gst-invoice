<?php

namespace AnjanTalukdar\GstInvoice\Data;

use AnjanTalukdar\GstInvoice\Enums\IndianState;
use JsonSerializable;

class SupplierInput implements JsonSerializable
{
    public string $stateCode = '';

    public function __construct(
        public string $name = '',
        public ?string $tradeName = null,
        public ?string $gstin = null,
        public ?string $pan = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $stateName = null,
        IndianState|string|null $stateCode = null,
        public ?string $pincode = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?BankDetailsInput $bankDetails = null
    ) {
        $this->stateCode = $this->formatStateCode($stateCode);
    }

    public static function make(
        string $name = '',
        ?string $tradeName = null,
        ?string $gstin = null,
        ?string $pan = null,
        ?string $address = null,
        ?string $city = null,
        ?string $stateName = null,
        IndianState|string|null $stateCode = null,
        ?string $pincode = null,
        ?string $email = null,
        ?string $phone = null,
        ?BankDetailsInput $bankDetails = null
    ): self {
        return new self(
            name: $name,
            tradeName: $tradeName,
            gstin: $gstin,
            pan: $pan,
            address: $address,
            city: $city,
            stateName: $stateName,
            stateCode: $stateCode,
            pincode: $pincode,
            email: $email,
            phone: $phone,
            bankDetails: $bankDetails
        );
    }

    public static function fromArray(array $data): self
    {
        $bankDetails = isset($data['bank_details']) && is_array($data['bank_details'])
            ? BankDetailsInput::fromArray($data['bank_details'])
            : ($data['bank_details'] ?? null);

        return new self(
            name: (string)($data['name'] ?? ''),
            tradeName: $data['trade_name'] ?? ($data['tradeName'] ?? null),
            gstin: $data['gstin'] ?? null,
            pan: $data['pan'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            stateName: $data['state_name'] ?? ($data['state'] ?? null),
            stateCode: $data['state_code'] ?? null,
            pincode: $data['pincode'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            bankDetails: $bankDetails instanceof BankDetailsInput ? $bankDetails : null
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

    public function bankDetails(?BankDetailsInput $bankDetails): self
    {
        $this->bankDetails = $bankDetails;
        return $this;
    }

    protected function formatStateCode(IndianState|string|null $stateCode): string
    {
        if ($stateCode instanceof IndianState) {
            return $stateCode->value;
        }

        if ($stateCode !== null && $stateCode !== '') {
            return str_pad((string)$stateCode, 2, '0', STR_PAD_LEFT);
        }

        return '';
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'trade_name' => $this->tradeName,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'address' => $this->address,
            'city' => $this->city,
            'state_name' => $this->stateName,
            'state_code' => $this->stateCode,
            'pincode' => $this->pincode,
            'email' => $this->email,
            'phone' => $this->phone,
            'bank_details' => $this->bankDetails?->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
