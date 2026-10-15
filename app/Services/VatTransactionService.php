<?php

namespace App\Services;

use App\Models\VatTransaction;

class VatTransactionService
{
    public static function storeVatTransactions($business, $detail, $product, $actualPrice, $party = null)
    {
        if (!$product->vat_id) {
            return 0;
        }

        $state_id = branch_count()
            ? auth()->user()->active_branch?->state_id ?? $business->state_id
            : $business->state_id;

        $itemVat = $product->loadMissing('vat.stateVats.childVat:id,rate')->vat;

        if (!$itemVat) {
            return 0;
        }

        if ($itemVat->manage_state) {

            $type = (!$party || $state_id == ($party->state_id ?? null))
                ? 'inner'
                : 'outer';

            $vats = $itemVat->stateVats
                ->where('type', $type)
                ->pluck('childVat')
                ->filter();
        } else {

            if ($itemVat->sub_vat) {
                $vats = collect($itemVat->sub_vat);
            } else {
                $vats = collect([
                    (object)[
                        'id' => $itemVat->id,
                        'rate' => $itemVat->rate
                    ]
                ]);
            }
        }

        return self::calculateAndStore($business, $detail, $vats, $actualPrice);
    }

    protected static function calculateAndStore($business, $detail, $vats, $actualPrice)
    {
        if ($vats->isEmpty()) {
            return 0;
        }

        $totalRate = $vats->sum('rate');
        $totalAmount = $actualPrice * $detail->quantities;

        if ($totalRate <= 0) {
            return 0;
        }

        $taxData = [];

        foreach ($vats as $vat) {

            $rate = $vat->rate ?? $vat['rate'] ?? 0;
            $vatAmount = ($totalAmount * $rate) / (100 + $totalRate);

            if ($vatAmount <= 0) {
                continue;
            }

            $taxData[] = [
                'business_id'  => $business->id,
                'vatable_id'   => $detail->id,
                'vatable_type' => get_class($detail),
                'vat_id'       => $vat->id ?? $vat['id'] ?? 0,
                'vat_rate'     => $rate,
                'vat_amount'   => $vatAmount,
                'branch_id'    => auth()->user()->branch_id ?? auth()->user()->active_branch_id ?? null,
            ];
        }

        if ($taxData) {
            VatTransaction::insert($taxData);
        }

        return collect($taxData)->sum('vat_amount');
    }

    public static function productReturnVatCalculation($detail, $return_qty, $return_detail, $total_qty)
    {
        $transactions = VatTransaction::where([
            'vatable_id' => $detail->id,
            'vatable_type' => get_class($detail)
        ])->get();

        $taxData = [];

        foreach ($transactions as $trx) {

            $return_vat = ($trx->vat_amount / $total_qty) * $return_qty;

            $taxData[] = [
                'business_id'  => $trx->business_id,
                'vatable_id'   => $return_detail->id,
                'vatable_type' => get_class($return_detail),
                'vat_id'       => $trx->vat_id,
                'vat_rate'     => $trx->vat_rate,
                'vat_amount'   => $return_vat,
                'branch_id'    => auth()->user()->branch_id ?? auth()->user()->active_branch_id ?? null,
            ];
        }

        if ($taxData) {
            VatTransaction::insert($taxData);
        }
    }
}
