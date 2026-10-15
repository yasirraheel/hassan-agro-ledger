<?php

namespace Modules\Business\App\Http\Controllers;

use App\Models\Income;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Business\App\Exports\ExportIncome;

class AcnooIncomeReportController extends Controller
{
    public function index()
    {
        $total_income = Income::where('business_id', auth()->user()->business_id)->sum('amount');
        $income_reports = Income::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)->latest()->paginate(20);
        return view('business::reports.income.income-reports', compact('income_reports','total_income'));
    }

    public function acnooFilter(Request $request)
    {
        $income_reports = Income::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('incomeFor', 'like', '%' . $request->search . '%')
                      ->orwhere('paymentType', 'like', '%' . $request->search . '%')
                      ->orwhere('amount', 'like', '%' . $request->search . '%')
                      ->orwhere('referenceNo', 'like', '%' . $request->search . '%')
                        ->orWhereHas('category', function ($q) use ($request) {
                            $q->where('categoryName', 'like', '%' . $request->search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('business::reports.income.datas', compact('income_reports'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function generatePDF(Request $request)
    {
        $income_reports = Income::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)->latest()->get();
        $pdf = Pdf::loadView('business::reports.income.pdf', compact('income_reports'));
        return $pdf->download('income.report.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ExportIncome, 'income.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportIncome, 'income.csv');
    }
}
