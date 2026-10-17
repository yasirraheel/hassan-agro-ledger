<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class BusinessInvoiceSettingController extends Controller
{
    public function index()
    {
        $businessId = auth()->user()->business_id;
        $saved = Option::where('key', 'invoice_setting_' . $businessId)->first();
        if ($saved) return response()->json(['message' => __('Invoice size fetched successfully.'), 'invoice_size' => $saved->value]);

        $invoiceSetting = Option::where('key', 'invoice_settings')
            ->whereJsonContains('value->business_id', $businessId)
            ->first();

        if ($invoiceSetting && isset($invoiceSetting->value['invoice_size'])) {
            return response()->json([
                'message' => __('Invoice size fetched successfully.'),
                'invoice_size' => $invoiceSetting->value['invoice_size'],
            ]);
        } else {
            return response()->json([
                'message' => __('Invoice size not found.'),
                'invoice_size' => 'a4',
            ]);
        }
    }

    public function updateInvoice(Request $request)
    {
        $request->validate([
            'invoice_size' => 'required|string|max:100|in:a4,3_inch_80mm,2_inch_58mm,standard_a4',
        ]);

        $key = 'invoice_setting_' . auth()->user()->business_id;

        Option::updateOrCreate(
            ['key' => $key],
            ['value' => $request->invoice_size]
        );

        Cache::forget($key);

        return response()->json([
            'message' => __('Invoice size updated successfully.'),
        ]);

    }
}
