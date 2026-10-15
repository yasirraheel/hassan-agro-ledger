<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;

class AcnooStateController extends Controller
{
    public function index(Request $request)
    {
        $states = State::when($request->search, function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        })->latest()->paginate($request->per_page ?? 20)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.states.datas', compact('states'))->render()
            ]);
        }

        $countries = Country::latest()->get();

        return view('admin.states.index', compact('states', 'countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
        ]);

        State::create($request->all());

        return response()->json([
            'message' => __('State saved successfully'),
            'redirect' => route('admin.states.index')
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name'  => 'required|string|max:255',
        ]);

        $state = State::findOrFail($id);

        $state->update($request->all());

        return response()->json([
            'message' => __('State updated successfully'),
            'redirect' => route('admin.states.index')
        ]);
    }


    public function destroy(string $id)
    {
        $state = State::findOrFail($id);

        $state->delete();

        return response()->json([
            'message' => __('States deleted successfully'),
            'redirect' => route('admin.states.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $state = State::findOrFail($id);
        $state->update(['status' => $request->status]);
        return response()->json(['message' => 'State']);
    }

    public function deleteAll(Request $request)
    {
        State::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => __('Selected State deleted successfully'),
            'redirect' => route('admin.states.index')
        ]);
    }
}
