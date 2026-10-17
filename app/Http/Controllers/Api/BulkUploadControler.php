<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Imports\ProductImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class BulkUploadControler extends Controller
{
    public function rows(Request $request)
    {
        $request->validate(['rows' => 'required|array|min:2|max:10001', 'rows.*' => 'array|max:21']);
        $businessId = auth()->user()->business_id;
        $branchId = auth()->user()->branch_id ?? auth()->user()->active_branch_id;
        $rows = collect($request->rows)->map(fn ($row) => array_pad($row, 21, null));
        (new ProductImport($businessId, $branchId))->collection($rows);
        $codes = $rows->skip(1)->map(fn ($row) => trim($row[5] ?? ''))->filter()->unique();
        $entities = ['products' => \App\Models\Product::with('stocks')->where('business_id', $businessId)->whereIn('productCode', $codes)->get()];
        foreach (['categories' => [\App\Models\Category::class, 'categoryName', 1], 'units' => [\App\Models\Unit::class, 'unitName', 2], 'brands' => [\App\Models\Brand::class, 'brandName', 3], 'vats' => [\App\Models\Vat::class, 'name', 10], 'product-models' => [\App\Models\ProductModel::class, 'name', 17]] as $resource => [$model, $field, $column]) {
            $names = $rows->skip(1)->map(fn ($row) => trim($row[$column] ?? ''))->unique();
            $entities[$resource] = $model::where('business_id', $businessId)->whereIn($field, $names)->get();
        }
        return response()->json(['message' => __('Bulk upload successfully.'), 'data' => ['_sync_entities' => $entities]]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        $businessId = auth()->user()->business_id;
        $branchId = auth()->user()->branch_id ?? auth()->user()->active_branch_id;

        Excel::import(new ProductImport($businessId, $branchId), $request->file('file'));

        return response()->json([
            'message' => __('Bulk upload successfully.')
        ]);
    }
}
