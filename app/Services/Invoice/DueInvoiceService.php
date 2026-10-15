<?php

namespace App\Services\Invoice;

use App\Models\DueCollect;
use App\Models\Party;
use App\Models\PaymentType;

class DueInvoiceService
{
    public function getInvoiceDataByUniqueId(string $unique_id)
    {
        $due_collect = DueCollect::with('user:id,name,role', 'party:id,name,email,phone,type', 'payment_type:id,name', 'business:id,companyName,address,phoneNumber,email,vat_name,vat_no,meta')
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        $business_id = $due_collect->business_id;

        $transactionTypes = $due_collect->transactions
            ->map(function ($transaction) {
                if ($transaction->transaction_type === 'bank_payment' && !empty($transaction->paymentType?->name)) {
                    return $transaction->paymentType?->name;
                }
                return $transaction->transaction_type ? ucfirst(explode('_', $transaction->transaction_type)[0]) : '';
            })
            ->unique()
            ->implode(', ');

        $party = Party::with('dueCollect.business')->find($due_collect->party_id);
        $bank_detail = PaymentType::where('business_id', $business_id)->where('show_in_invoice', 1)->latest()->first();

        return compact(
            'due_collect',
            'party',
            'transactionTypes',
            'bank_detail',
        );
    }

    public function getInvoiceData(int $id, ?string $phone = null)
    {

        $business_id = auth()->check() ? auth()->user()->business_id : null;

        $party = null;

        if ($phone) {
            $party = Party::where('phone', $phone)->first();
            if (!$party) {
                abort(404, 'Not found');
            }
            $business_id = $party->business_id;
        }

        $due_collect = DueCollect::with('user:id,name,role', 'party:id,name,email,phone,type', 'payment_type:id,name', 'business:id,companyName,address,phoneNumber,email,vat_name,vat_no,meta')
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->firstOrFail();

        $transactionTypes = $due_collect->transactions
            ->map(function ($transaction) {
                if ($transaction->transaction_type === 'bank_payment' && !empty($transaction->paymentType?->name)) {
                    return $transaction->paymentType->name;
                }
                return $transaction->transaction_type ? ucfirst(explode('_', $transaction->transaction_type)[0]) : '';
            })
            ->unique()
            ->implode(', ');

        $party = Party::with('dueCollect.business')->find($due_collect->party_id);
        $bank_detail = PaymentType::where('business_id', $business_id)->where('show_in_invoice', 1)->latest()->first();

        return compact(
            'due_collect',
            'party',
            'transactionTypes',
            'bank_detail',
        );
    }
}
