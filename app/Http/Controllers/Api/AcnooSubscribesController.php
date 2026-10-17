<?php

namespace App\Http\Controllers\Api;

use App\Models\PlanSubscribe;
use App\Http\Controllers\Controller;

class AcnooSubscribesController extends Controller
{
    public function index()
    {
        $subscribes = PlanSubscribe::where('business_id', auth()->user()->business_id)->latest()->get();
        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $subscribes
        ]);
    }
}
