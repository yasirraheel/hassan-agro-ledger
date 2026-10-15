<?php

namespace Modules\Business\App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturnDetails;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Business\App\Exports\ExportSalesReturn;

class AcnooSaleReturnReportController extends Controller
{
    public function index()
    {
        $total_return = SaleReturnDetails::whereHas('saleReturn', function ($query) {
                            $query->whereHas('sale', function ($q) {
                                $q->where('business_id', auth()->user()->business_id);
                            });
                        })->sum('return_amount');

        $sales =  Sale::with([
                        'user:id,name',
                        'party:id,name',
                        'details',
                        'details.product:id,productName,category_id',
                        'details.product.category:id,categoryName',
                        'saleReturns' => function ($query) {
                            $query->withSum('details as total_return_amount', 'return_amount');
                        }
                    ])
                    ->where('business_id', auth()->user()->business_id)
                    ->whereHas('saleReturns')
                    ->latest()
                    ->paginate(20);

        return view('business::reports.sales-return.sale-reports', compact('sales','total_return'));
    }

    public function acnooFilter(Request $request)
    {
        $sales =  Sale::with([
            'user:id,name',
            'party:id,name',
            'details',
            'details.product:id,productName,category_id',
            'details.product.category:id,categoryName',
            'saleReturns' => function ($query) {
                $query->withSum('details as total_return_amount', 'return_amount');
            }
        ])
            ->where('business_id', auth()->user()->business_id)
            ->whereHas('saleReturns')
            ->when(request('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('invoiceNumber', 'like', '%' . $request->search . '%')
                        ->orWhereHas('party', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('business::reports.sales-return.datas', compact('sales'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function generatePDF(Request $request)
    {
        $sales =  Sale::with([
            'user:id,name',
            'party:id,name',
            'details',
            'details.product:id,productName,category_id',
            'details.product.category:id,categoryName',
            'saleReturns' => function ($query) {
                $query->withSum('details as total_return_amount', 'return_amount');
            }
        ])
            ->where('business_id', auth()->user()->business_id)
            ->whereHas('saleReturns')
            ->get();
        $pdf = Pdf::loadView('business::reports.sales-return.pdf', compact('sales'));
        return $pdf->download('sales-return-report.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ExportSalesReturn, 'sales-return.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportSalesReturn, 'sales-return.csv');
    }
}
