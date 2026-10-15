<?php

namespace Modules\Business\App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\DueCollect;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Business\App\Exports\ExportTransaction;

class AcnooTransactionHistoryReportController extends Controller
{
    public function index()
    {
        $transcations = DueCollect::where('business_id', auth()->user()->business_id)->with('party:id,name')->latest()->paginate(20);
        return view('business::reports.transaction-history.transaction-reports', compact('transcations'));
    }

    public function acnooFilter(Request $request)
    {
        $transcations = DueCollect::where('business_id', auth()->user()->business_id)->with('party:id,name')
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('paymentType', 'like', '%' . $request->search . '%')
                       ->orwhere('totalDue', 'like', '%' . $request->search . '%')
                       ->orwhere('invoiceNumber', 'like', '%' . $request->search . '%')
                       ->orwhere('payDueAmount', 'like', '%' . $request->search . '%')
                       ->orWhereHas('party', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('business::reports.transaction-history.datas', compact('transcations'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function generatePDF(Request $request)
    {
        $transcations = DueCollect::where('business_id', auth()->user()->business_id)->with('party:id,name,email')->latest()->get();
        $pdf = Pdf::loadView('business::reports.transaction-history.pdf', compact('transcations'));
        return $pdf->download('transcations.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ExportTransaction, 'transaction-history.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportTransaction, 'transaction-history.csv');
    }
}
