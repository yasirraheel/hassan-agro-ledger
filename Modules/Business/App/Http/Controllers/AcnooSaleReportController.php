<?php

namespace Modules\Business\App\Http\Controllers;

use Modules\Business\App\Exports\ExportExcelCsv;
use App\Models\Sale;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class AcnooSaleReportController extends Controller
{
    public function index()
    {
        $total_sale = Sale::where('business_id', auth()->user()->business_id)->sum('totalAmount');

        $sales = Sale::with('user:id,name', 'party:id,name,email,phone,type', 'business:id,companyName')
                    ->where('business_id', auth()->user()->business_id)
                    ->latest()
                    ->paginate(20);

        return view('business::reports.sales.sale-reports', compact('sales','total_sale'));
    }

    public function acnooFilter(Request $request)
    {
        $sales = Sale::with('user:id,name', 'party:id,name,email,phone,type')
            ->where('business_id', auth()->user()->business_id)
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [$request->from_date, $request->to_date]);
            })
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('paymentType', 'like', '%' . $request->search . '%')
                    ->orWhere('invoiceNumber', 'like', '%' . $request->search . '%')
                        ->orWhereHas('party', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('business::reports.sales.datas', compact('sales'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function generatePDF(Request $request)
    {
        $sales = Sale::with('user:id,name', 'party:id,name,email,phone,type')->where('business_id', auth()->user()->business_id)->latest()->get();
        $pdf = Pdf::loadView('business::reports.sales.pdf', compact('sales'));
        return $pdf->download('reports.sales.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ExportExcelCsv, 'sale.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportExcelCsv, 'sale.csv');
    }
}
