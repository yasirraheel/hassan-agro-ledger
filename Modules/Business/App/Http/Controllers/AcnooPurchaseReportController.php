<?php

namespace Modules\Business\App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Modules\Business\App\Exports\purchaseExport;
use Maatwebsite\Excel\Facades\Excel;

class AcnooPurchaseReportController extends Controller
{
    public function index()
    {
        $total_purchase = Purchase::where('business_id', auth()->user()->business_id)->sum('totalAmount');

        $purchases = Purchase::with('details','party','details.product','details.product.category')
                        ->where('business_id', auth()->user()->business_id)
                        ->latest()
                        ->paginate(20);

        return view('business::reports.purchase.purchase-reports', compact('purchases','total_purchase'));
    }

    public function acnooFilter(Request $request)
    {
        $purchases = Purchase::with('details')
        ->where('business_id', auth()->user()->business_id)
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
                'data' => view('business::reports.purchase.datas', compact('purchases'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function generatePDF(Request $request)
    {
        $purchases = Purchase::with('details','party','details.product','details.product.category')->where('business_id', auth()->user()->business_id)->latest()->get();
        $pdf = Pdf::loadView('business::reports.purchase.pdf', compact('purchases'));
        return $pdf->download('purchase.report.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new purchaseExport, 'purchase.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new purchaseExport, 'purchase.csv');
    }
}
