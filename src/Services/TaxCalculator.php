<?php

namespace AnjanTalukdar\GstInvoice\Services;

use AnjanTalukdar\GstInvoice\Contracts\TaxCalculatorInterface;
use AnjanTalukdar\GstInvoice\Data\BillingSummaryData;
use AnjanTalukdar\GstInvoice\Data\InvoiceItemData;
use AnjanTalukdar\GstInvoice\Data\TaxSummaryData;
use AnjanTalukdar\GstInvoice\Enums\OddPaisaWeightage;
use AnjanTalukdar\GstInvoice\Enums\RoundingStrategy;
use AnjanTalukdar\GstInvoice\Enums\TaxCategory;
use AnjanTalukdar\GstInvoice\ValueObjects\Money;

class TaxCalculator implements TaxCalculatorInterface
{
    public function calculate(array $items, array $options = []): BillingSummaryData
    {
        $discountMode = $options['discount_mode'] ?? 'bill';
        $gstMode = $options['gst_mode'] ?? config('gst-invoice.gst_mode', 'inclusive');
        $isInclusive = ($gstMode === 'inclusive');

        $supplierStateCode = $options['supplier_state_code'] ?? config('gst-invoice.supplier.state_code');
        $posStateCode = $options['pos_state_code'] ?? ($options['recipient_state_code'] ?? null);
        $posStateName = $options['pos_state_name'] ?? ($options['recipient_state_name'] ?? null);

        $supplierStateCodeFormatted = $supplierStateCode ? str_pad((string)$supplierStateCode, 2, '0', STR_PAD_LEFT) : null;
        $posStateCodeFormatted = $posStateCode ? str_pad((string)$posStateCode, 2, '0', STR_PAD_LEFT) : null;

        $isInterstate = (bool)($options['is_interstate'] ?? ($supplierStateCodeFormatted && $posStateCodeFormatted && $supplierStateCodeFormatted !== $posStateCodeFormatted));
        $isReverseCharge = (bool)($options['is_reverse_charge'] ?? false);

        $roundingStrategy = $options['rounding_strategy'] ?? config('gst-invoice.rounding_strategy', RoundingStrategy::STANDARD->value);
        $oddPaisaWeightage = $options['odd_paisa_weightage'] ?? config('gst-invoice.odd_paisa_weightage', OddPaisaWeightage::CGST->value);
        $oddPaisaWeightageVal = $oddPaisaWeightage instanceof OddPaisaWeightage ? $oddPaisaWeightage->value : (string)$oddPaisaWeightage;

        $billDiscountMoney = Money::of($options['discount'] ?? 0);

        // 1. Process initial item raw values
        $itemsData = [];
        $totalInitialTaxableMoney = Money::zero();

        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 1);
            $unitPriceMoney = Money::of($item['unit_price'] ?? 0);
            $gstRate = (float)($item['gst_rate'] ?? config('gst-invoice.default_gst_rate', 18));
            $taxCat = strtolower($item['tax_category'] ?? 'taxable');

            if ($taxCat !== TaxCategory::TAXABLE->value) {
                $gstRate = 0.00;
            }

            $itemDiscountMoney = Money::of($item['discount'] ?? ($item['item_discount'] ?? 0));

            $grossLineMoney = $unitPriceMoney->multiply($qty);

            if ($isInclusive && $gstRate > 0) {
                $taxableLineMoney = $grossLineMoney->subtract($itemDiscountMoney);
                $netBaseMoney = Money::of($taxableLineMoney->getRawAmount() / (1 + ($gstRate / 100)));
                $itemTaxableBeforeBillDiscountMoney = $netBaseMoney;
            } else {
                $itemTaxableBeforeBillDiscountMoney = max(0.00, $grossLineMoney->subtract($itemDiscountMoney)->getRawAmount()) > 0
                    ? $grossLineMoney->subtract($itemDiscountMoney)
                    : Money::zero();
            }

            $totalInitialTaxableMoney = $totalInitialTaxableMoney->add($itemTaxableBeforeBillDiscountMoney);

            $codeType = strtoupper($item['code_type'] ?? config('gst-invoice.default_code_type', 'SAC'));
            $defaultCode = $codeType === 'HSN' ? config('gst-invoice.default_hsn', '8471') : config('gst-invoice.default_sac', '998313');
            $code = (string)($item['code'] ?? $defaultCode);

            $itemsData[] = [
                'description' => $item['description'] ?? '',
                'code_type' => $codeType,
                'code' => $code,
                'tax_category' => $taxCat,
                'quantity' => $qty,
                'unit' => $item['unit'] ?? 'Pcs',
                'unit_price' => $unitPriceMoney->getAmount(),
                'gst_rate' => $gstRate,
                'item_discount_money' => $itemDiscountMoney,
                'taxable_before_bill_discount_money' => $itemTaxableBeforeBillDiscountMoney,
                'meta_data' => $item['meta_data'] ?? null,
                'sort_order' => (int)($item['sort_order'] ?? 0),
            ];
        }

        // 2. Allocate bill-level discount proportionally
        $allocatedDiscountsMoney = [];
        $remainingDiscountMoney = $billDiscountMoney;
        $totalItems = count($itemsData);

        foreach ($itemsData as $index => $itemData) {
            $taxableMoney = $itemData['taxable_before_bill_discount_money'];

            if ($totalInitialTaxableMoney->getAmount() > 0) {
                if ($index === $totalItems - 1) {
                    $allocMoney = $remainingDiscountMoney;
                } else {
                    $ratio = $taxableMoney->getAmount() / $totalInitialTaxableMoney->getAmount();
                    $allocMoney = Money::of($billDiscountMoney->getAmount() * $ratio)->round($roundingStrategy);
                    $remainingDiscountMoney = $remainingDiscountMoney->subtract($allocMoney);
                }
            } else {
                $allocMoney = Money::zero();
            }

            $allocatedDiscountsMoney[$index] = $allocMoney;
        }

        // 3. Compute final line item values & GST split
        $processedItems = [];
        $grossTaxableTotalMoney = Money::zero();
        $totalDiscountTotalMoney = Money::zero();
        $invoiceSubtotalMoney = Money::zero();
        $invoiceCgstMoney = Money::zero();
        $invoiceSgstMoney = Money::zero();
        $invoiceIgstMoney = Money::zero();
        $invoiceGstAmountMoney = Money::zero();
        $invoiceTotalMoney = Money::zero();

        $gstSlabs = [];

        foreach ($itemsData as $index => $itemData) {
            $qty = $itemData['quantity'];
            $unitPrice = $itemData['unit_price'];
            $itemDiscountMoney = $itemData['item_discount_money'];
            $allocDiscountMoney = $allocatedDiscountsMoney[$index];
            $gstRate = $itemData['gst_rate'];
            $taxCat = $itemData['tax_category'];

            $undiscountedGrossMoney = Money::of($unitPrice * $qty);

            $finalTaxableMoney = max(0.00, $itemData['taxable_before_bill_discount_money']->subtract($allocDiscountMoney)->getAmount()) > 0
                ? $itemData['taxable_before_bill_discount_money']->subtract($allocDiscountMoney)->round($roundingStrategy)
                : Money::zero();

            if ($taxCat === TaxCategory::TAXABLE->value && $gstRate > 0) {
                $finalGstMoney = $finalTaxableMoney->percentage($gstRate)->round($roundingStrategy);
            } else {
                $finalGstMoney = Money::zero();
            }

            // Intra vs Inter split logic with Odd Paisa Weightage
            $cgstMoney = Money::zero();
            $sgstMoney = Money::zero();
            $igstMoney = Money::zero();

            if ($finalGstMoney->getAmount() > 0) {
                if ($isInterstate) {
                    $igstMoney = $finalGstMoney;
                } else {
                    $baseHalfAmount = floor(($finalGstMoney->getAmount() / 2) * 100) / 100;
                    $baseHalfMoney = Money::of($baseHalfAmount);
                    $remainderPaisa = round(($finalGstMoney->getAmount() - ($baseHalfAmount * 2)), 2);

                    if ($remainderPaisa > 0) {
                        if ($oddPaisaWeightageVal === 'sgst') {
                            $sgstMoney = $baseHalfMoney->add($remainderPaisa);
                            $cgstMoney = $baseHalfMoney;
                        } else {
                            $cgstMoney = $baseHalfMoney->add($remainderPaisa);
                            $sgstMoney = $baseHalfMoney;
                        }
                    } else {
                        $cgstMoney = $baseHalfMoney;
                        $sgstMoney = $baseHalfMoney;
                    }
                }
            }

            $lineTotalMoney = $finalTaxableMoney->add($finalGstMoney);

            $totalLineDiscountMoney = $itemDiscountMoney->add($allocDiscountMoney);

            $processedItems[] = new InvoiceItemData(
                description: $itemData['description'],
                codeType: $itemData['code_type'],
                code: $itemData['code'],
                taxCategory: $taxCat,
                quantity: $qty,
                unit: $itemData['unit'],
                unitPrice: $unitPrice,
                itemDiscount: $itemDiscountMoney->getAmount(),
                billDiscount: $allocDiscountMoney->getAmount(),
                taxableAmount: $finalTaxableMoney->getAmount(),
                gstRate: $gstRate,
                cgstAmount: $cgstMoney->getAmount(),
                sgstAmount: $sgstMoney->getAmount(),
                igstAmount: $igstMoney->getAmount(),
                gstAmount: $finalGstMoney->getAmount(),
                totalAmount: $lineTotalMoney->getAmount(),
                metaData: $itemData['meta_data'],
                sortOrder: $itemData['sort_order']
            );

            // Accumulate Slab Summaries
            $rateStr = (string)$gstRate;
            if (!isset($gstSlabs[$rateStr])) {
                $gstSlabs[$rateStr] = [
                    'rate' => $gstRate,
                    'half_rate' => $gstRate / 2,
                    'taxable' => 0.00,
                    'cgst_amount' => 0.00,
                    'sgst_amount' => 0.00,
                    'igst_amount' => 0.00,
                    'total_gst' => 0.00,
                ];
            }
            $gstSlabs[$rateStr]['taxable'] += $finalTaxableMoney->getAmount();
            $gstSlabs[$rateStr]['cgst_amount'] += $cgstMoney->getAmount();
            $gstSlabs[$rateStr]['sgst_amount'] += $sgstMoney->getAmount();
            $gstSlabs[$rateStr]['igst_amount'] += $igstMoney->getAmount();
            $gstSlabs[$rateStr]['total_gst'] += $finalGstMoney->getAmount();

            $grossTaxableTotalMoney = $grossTaxableTotalMoney->add($undiscountedGrossMoney);
            $totalDiscountTotalMoney = $totalDiscountTotalMoney->add($totalLineDiscountMoney);
            $invoiceSubtotalMoney = $invoiceSubtotalMoney->add($finalTaxableMoney);
            $invoiceCgstMoney = $invoiceCgstMoney->add($cgstMoney);
            $invoiceSgstMoney = $invoiceSgstMoney->add($sgstMoney);
            $invoiceIgstMoney = $invoiceIgstMoney->add($igstMoney);
            $invoiceGstAmountMoney = $invoiceGstAmountMoney->add($finalGstMoney);
            $invoiceTotalMoney = $invoiceTotalMoney->add($lineTotalMoney);
        }

        // Format slabs
        foreach ($gstSlabs as &$slab) {
            $slab['taxable'] = round($slab['taxable'], 2);
            $slab['cgst_amount'] = round($slab['cgst_amount'], 2);
            $slab['sgst_amount'] = round($slab['sgst_amount'], 2);
            $slab['igst_amount'] = round($slab['igst_amount'], 2);
            $slab['total_gst'] = round($slab['total_gst'], 2);
        }

        // Round Off Adjustment
        $exactGrandTotal = $invoiceTotalMoney->getAmount();
        $roundedGrandTotal = Money::of($exactGrandTotal)->round($roundingStrategy)->getAmount();
        $roundOffMoney = Money::of($roundedGrandTotal - $exactGrandTotal)->round($roundingStrategy);

        $summary = new TaxSummaryData(
            grossTaxable: $grossTaxableTotalMoney->getAmount(),
            discount: $totalDiscountTotalMoney->getAmount(),
            subtotal: $invoiceSubtotalMoney->getAmount(),
            cgstAmount: $invoiceCgstMoney->getAmount(),
            sgstAmount: $invoiceSgstMoney->getAmount(),
            igstAmount: $invoiceIgstMoney->getAmount(),
            gstAmount: $invoiceGstAmountMoney->getAmount(),
            roundOff: $roundOffMoney->getAmount(),
            total: $roundedGrandTotal,
            paidAmount: 0.00,
            dueAmount: $roundedGrandTotal
        );

        $auditTrail = [
            'discount_mode' => $discountMode,
            'is_inclusive' => $isInclusive,
            'odd_paisa_weightage' => $oddPaisaWeightageVal,
            'discounts' => array_map(fn(InvoiceItemData $item) => [
                'code' => $item->code,
                'item_discount' => $item->itemDiscount,
                'bill_discount' => $item->billDiscount,
            ], $processedItems),
        ];

        return new BillingSummaryData(
            isInterstate: $isInterstate,
            isReverseCharge: $isReverseCharge,
            posStateName: $posStateName,
            posStateCode: $posStateCodeFormatted,
            discountMode: $discountMode,
            summary: $summary,
            items: $processedItems,
            gstSlabs: array_values($gstSlabs),
            auditTrail: $auditTrail
        );
    }
}
