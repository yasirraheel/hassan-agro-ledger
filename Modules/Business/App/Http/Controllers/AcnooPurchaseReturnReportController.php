<?php

namespace Modules\Business\App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseReturnDetail;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Business\App\Exports\ExportPurchaseReturn;

class AcnooPurchaseReturnReportController extends Controller
{
    public function index()
    {
        $total_return = PurchaseReturnDetail::whereHas('purchaseReturn', function ($query) {
                            $query->whereHas('purchase', function ($q) {
                                $q->where('business_id', auth()->user()->business_id);
                            });
                        })->sum('return_amount');

        $purchases = Purchase::with([
            'user:id,name',
            'party:id,name,email,phone,type',
            'details:id,purchase_id,product_id,productPurchasePrice,quantities',
            'details.product:id,productName,category_id',
            'details.product.category:id,categoryName',
            'purchaseReturns' => function ($query) {
                $query->withSum('details as total_return_amount', 'return_amount');
            }
        ])
            ->where('business_id', auth()->user()->business_id)
            ->whereHas('purchaseReturns')
            ->latest()
            ->paginate(20);

        return view('business::reports.purchase-return.purchase-reports', compact('purchases','total_return'));
    }

    public function acnooFilter(Request $request)
    {
        $purchases = Purchase::with([
            'user:id,name',
            'party:id,name,email,phone,type',
            'details:id,purchase_id,product_id,productPurchasePrice,quantities',
            'details.product:id,productName,category_id',
            'details.product.category:id,categoryName',
            'purchaseReturns' => function ($query) {
                $query->withSum('details as total_return_amount', 'return_amount');
            }
        ])
            ->where('business_id', auth()->user()->business_id)
            ->whereHas('purchaseReturns')
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
                'data' => view('business::reports.purchase-return.datas', compact('purchases'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function generatePDF(Request $request)
    {
        $purchases = Purchase::with([
            'user:id,name',
            'party:id,name,email,phone,type',
            'details:id,purchase_id,product_id,productPurchasePrice,quantities',
            'details.product:id,productName,category_id',
            'details.product.category:id,categoryName',
            'purchaseReturns' => function ($query) {
                $query->withSum('details as total_return_amount', 'return_amount');
            }
        ])
            ->where('business_id', auth()->user()->business_id)
            ->whereHas('purchaseReturns')
            ->get();
        $pdf = Pdf::loadView('business::reports.purchase-return.pdf', compact('purchases'));
        return $pdf->download('purchase.report.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new ExportPurchaseReturn, 'purchase-return.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportPurchaseReturn, 'purchase-return.csv');
    }
}
