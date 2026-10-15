<?php

namespace Modules\Business\App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Business\App\Exports\ExportExpense;

class AcnooExpenseReportController extends Controller
{
    public function index()
    {
        $total_expense = Expense::where('business_id', auth()->user()->business_id)->sum('amount');
        $expense_reports = Expense::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)->latest()->paginate(20);
        return view('business::reports.expense.expense-reports', compact('expense_reports','total_expense'));
    }


    public function acnooFilter(Request $request)
    {
        $expense_reports = Expense::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('expanseFor', 'like', '%' . $request->search . '%')
                      ->orwhere('paymentType', 'like', '%' . $request->search . '%')
                      ->orwhere('referenceNo', 'like', '%' . $request->search . '%')
                      ->orwhere('amount', 'like', '%' . $request->search . '%')
                        ->orWhereHas('category', function ($q) use ($request) {
                            $q->where('categoryName', 'like', '%' . $request->search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('business::reports.expense.datas', compact('expense_reports'))->render()
            ]);
        }
        return redirect(url()->previous());
    }


    public function generatePDF(Request $request)
    {
        $expense_reports = Expense::with('category:id,categoryName')->where('business_id', auth()->user()->business_id)->latest()->get();
        $pdf = Pdf::loadView('business::reports.expense.pdf', compact('expense_reports'));
        return $pdf->download('expense.report.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ExportExpense, 'expense.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportExpense, 'expense.csv');
    }
}
