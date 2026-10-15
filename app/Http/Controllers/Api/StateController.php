<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $states = State::orderBy('name')
            ->when($request->filled('country_id'), function ($q) use ($request) {
                $q->where('country_id', $request->country_id);
            });

        if ($request->has('no_paginate') && $request->no_paginate == true) {
            $states = $states->get();
            $data = ['data' => $states];
        } else {
            $states = $states->paginate(20);
            $data = $states;
        }

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data
        ]);
    }
}
