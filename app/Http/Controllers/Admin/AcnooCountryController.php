<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\HasUploader;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AcnooCountryController extends Controller
{
    use HasUploader;

    public function index(Request $request)
    {
        $countries = Country::orderBy('name')->when($request->search, function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        })->latest()->paginate($request->per_page ?? 20)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.countries.datas', compact('countries'))->render()
            ]);
        }

        return view('admin.countries.index', compact('countries'));
    }

    public function create()
    {
        return view('admin.countries.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:countries',
            'code' => 'required|string|unique:countries',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        Country::create($request->except('image') + [
            'image' => $request->image ? $this->upload($request, 'image') : null,
        ]);

        return response()->json([
            'message'   => __('Country Created successfully'),
            'redirect'  => route('admin.countries.index')
        ]);
    }


    public function edit(Country $country)
    {
        return view('admin.countries.edit', compact('country'));
    }

    public function update(Request $request, Country $country)
    {
        $request->validate([
            'name' => 'required|string|unique:countries,name,' . $country->id,
            'code' => 'required|string|unique:countries,code,' . $country->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        $country->update($request->except('image') + [
            'image' => $request->image ? $this->upload($request, 'image', $country->image) : $country->image,
        ]);

        return response()->json([
            'message'   => __('Country updated successfully'),
            'redirect'  => route('admin.countries.index')
        ]);
    }

    public function destroy(Country $country)
    {
        if (file_exists($country->image)) {
            Storage::delete($country->image);
        }

        $country->delete();

        return response()->json([
            'message' => __('Country deleted successfully'),
            'redirect' => route('admin.countries.index')
        ], 200);
    }

    public function status(Request $request, $id)
    {
        $status = Country::findOrFail($id);
        $status->update(['status' => $request->status]);
        return response()->json(['message' => 'Country']);
    }

    public function deleteAll(Request $request)
    {
        $countries = Country::whereIn('id', $request->ids)->get();

        foreach ($countries as $country) {
            if ($country->image && file_exists(public_path($country->image))) {
                Storage::delete($country->image);
            }
        }

        Country::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => __('Selected Country deleted successfully'),
            'redirect' => route('admin.countries.index')
        ]);
    }


}
