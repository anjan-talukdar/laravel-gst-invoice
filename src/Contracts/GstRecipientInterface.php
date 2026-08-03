<?php

namespace AnjanTalukdar\GstInvoice\Contracts;

interface GstRecipientInterface
{
    public function getGstBillingName(): string;
    public function getGstBillingEmail(): ?string;
    public function getGstBillingPhone(): ?string;
    public function getGstBillingGstin(): ?string;
    public function getGstBillingPan(): ?string;
    public function getGstBillingAddress(): ?string;
    public function getGstBillingCity(): ?string;
    public function getGstBillingStateName(): ?string;
    public function getGstBillingStateCode(): ?string;
    public function getGstBillingPincode(): ?string;
}
