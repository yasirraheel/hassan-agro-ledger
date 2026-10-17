<?php

namespace App\Http\Controllers\Api;

use App\Models\Currency;
use App\Models\UserCurrency;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AcnooCurrencyController extends Controller
{
    public function select(\Illuminate\Http\Request $request)
    {
        $request->validate(['currency_id' => 'required|exists:currencies,id']);
        return $this->show($request->currency_id);
    }
    public function index()
    {
        $currencies = Currency::orderBy('is_default', 'desc')->orderBy('status', 'desc')->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $currencies
        ]);
    }

    public function show($id)
    {
        $currency = Currency::findOrFail($id);
        $business_id = auth()->user()->business_id;

        $user_currency = UserCurrency::where('business_id', $business_id)->first();

        $user_currency->update([
            'name' => $currency->name,
            'code' => $currency->code,
            'rate' => $currency->rate,
            'symbol' => $currency->symbol,
            'position' => $currency->position,
            'country_name' => $currency->country_name,
        ]);

        cache()->forget("business_currency_" . $business_id);

        return response()->json([
            'message', __('Currency changed successfully'),
        ]);
    }
}
