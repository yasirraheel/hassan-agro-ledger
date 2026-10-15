<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $countries = Country::orderBy('name');

        if ($request->has('no_paginate') && $request->no_paginate == true) {
            $countries = $countries->get();
            $data = ['data' => $countries];
        } else {
            $countries = $countries->paginate(20);
            $data = $countries;
        }

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data
        ]);
    }
}
