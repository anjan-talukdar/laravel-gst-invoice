<?php

namespace AnjanTalukdar\GstInvoice\Data;

use JsonSerializable;

class BankDetailsInput implements JsonSerializable
{
    public function __construct(
        public string $bankName = '',
        public string $accountHolder = '',
        public string $accountNumber = '',
        public string $ifsc = '',
        public string $branch = ''
    ) {}

    public static function make(
        string $bankName = '',
        string $accountHolder = '',
        string $accountNumber = '',
        string $ifsc = '',
        string $branch = ''
    ): self {
        return new self(
            bankName: $bankName,
            accountHolder: $accountHolder,
            accountNumber: $accountNumber,
            ifsc: $ifsc,
            branch: $branch
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            bankName: (string)($data['bank_name'] ?? ($data['bankName'] ?? '')),
            accountHolder: (string)($data['account_holder'] ?? ($data['accountHolder'] ?? '')),
            accountNumber: (string)($data['account_number'] ?? ($data['accountNumber'] ?? '')),
            ifsc: (string)($data['ifsc'] ?? ''),
            branch: (string)($data['branch'] ?? '')
        );
    }

    public function bankName(string $bankName): self
    {
        $this->bankName = $bankName;
        return $this;
    }

    public function accountHolder(string $accountHolder): self
    {
        $this->accountHolder = $accountHolder;
        return $this;
    }

    public function accountNumber(string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function ifsc(string $ifsc): self
    {
        $this->ifsc = $ifsc;
        return $this;
    }

    public function branch(string $branch): self
    {
        $this->branch = $branch;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'bank_name' => $this->bankName,
            'account_holder' => $this->accountHolder,
            'account_number' => $this->accountNumber,
            'ifsc' => $this->ifsc,
            'branch' => $this->branch,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
