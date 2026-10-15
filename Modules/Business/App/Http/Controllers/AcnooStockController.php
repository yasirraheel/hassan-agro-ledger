<?php

namespace Modules\Business\App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Business\App\Exports\ExportCurrentStock;

class AcnooStockController extends Controller
{

    public function index()
    {
        $total_stock_value = Product::where('business_id', auth()->user()->business_id)->selectRaw('SUM(productSalePrice * productStock) as total_value')->value('total_value');
        $total_qty = Product::where('business_id', auth()->user()->business_id)->sum('productStock');

        if (request('low_stock')){
            $stocks = Product::where('business_id', auth()->user()->business_id)
                    ->orderBy('productStock', 'asc')
                    ->paginate(20);
        }else{
            $stocks = Product::where('business_id', auth()->user()->business_id)->latest()->paginate(20);
        }

        return view('business::stocks.index', compact('stocks','total_stock_value','total_qty'));
    }

    public function acnooFilter(Request $request)
    {
        $total_stock_value = Product::where('business_id', auth()->user()->business_id)->selectRaw('SUM(productSalePrice * productStock) as total_value')->value('total_value');
        $total_qty = Product::where('business_id', auth()->user()->business_id)->sum('productStock');

        $stocks = Product::where('business_id', auth()->user()->business_id)
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('productName', 'like', '%' . $request->search . '%')
                      ->orWhere('productSalePrice', 'like', '%' .$request->search . '%')
                      ->orWhere('productStock', 'like', '%' .$request->search . '%')
                      ->orWhere('productPurchasePrice', 'like', '%' .$request->search . '%');
                });
            })
            ->when($request->low_stock, function ($query) {
                $query->orderBy('productStock', 'asc');
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('business::stocks.datas', compact('stocks', 'total_stock_value', 'total_qty'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function generatePDF(Request $request)
    {
        $stocks = Product::where('business_id', auth()->user()->business_id)->latest()->get();
        $pdf = Pdf::loadView('business::stocks.pdf', compact('stocks'));
        return $pdf->download('current-stock.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ExportCurrentStock, 'current-stock.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportCurrentStock, 'current-stock.csv');
    }
}
