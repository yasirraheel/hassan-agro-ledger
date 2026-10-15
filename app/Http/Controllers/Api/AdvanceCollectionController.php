<?php

namespace App\Http\Controllers\Api;

use App\Events\MultiPaymentProcessed;
use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdvanceCollectionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'party_id' => 'required|exists:parties,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $party = Party::findOrFail($request->party_id);
            $payments = $request->payments ?? [];

            // Only count actual received money (exclude cheque)
            $payAmount = collect($payments)
                ->reject(fn($p) => strtolower($p['type'] ?? '') === 'cheque')
                ->sum(fn($p) => $p['amount'] ?? 0);

            if ($payAmount <= 0) {
                return response()->json([
                    'message' => __('No valid payment amount received (cheques are pending).')
                ], 400);
            }

            // Determine platform based on party type
            $platform = $party->type === 'Supplier' ? 'advance_pay' : 'advance_collect';

            $party->increment('wallet', $payAmount);

            event(new MultiPaymentProcessed(
                $payments,
                $party->id,
                $platform,
                $payAmount,
                $party->id,
                $request->date ?? now(),
                $request->note ?? null
            ));

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => [
                    'party_id' => $party->id,
                    'wallet' => $party->wallet,
                    'platform' => $platform,
                    'amount' => $payAmount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
