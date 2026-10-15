<?php

namespace App\Http\Controllers;

use App\Services\Invoice\DueInvoiceService;
use App\Services\Invoice\PurchaseInvoiceService;
use App\Services\Invoice\SaleInvoiceService;

class AcnooInvoiceController extends Controller
{
    public function saleInvoice($sale_id, $phone, SaleInvoiceService $service)
    {
        $data = $service->getInvoiceData($sale_id, $phone);
        return view('business::sales.invoice', $data);
    }

    public function saleInvoiceByUniqueId($unique_id, SaleInvoiceService $service)
    {
        $data = $service->getInvoiceDataByUniqueId($unique_id);
        return view('business::sales.invoice', $data);
    }

    public function purchaseInvoice($purchase_id, $phone, PurchaseInvoiceService $service)
    {
        $data = $service->getInvoiceData($purchase_id, $phone);
        return view('business::purchases.invoice', $data);
    }

    public function purchaseInvoiceByUniqueId($unique_id, PurchaseInvoiceService $service)
    {
        $data = $service->getInvoiceDataByUniqueId($unique_id);
        return view('business::purchases.invoice', $data);
    }

    public function dueInvoice($party_id, $phone, DueInvoiceService $service)
    {
        $data = $service->getInvoiceData($party_id, $phone);
        return view('business::dues.invoice', $data);
    }

    public function dueInvoiceByUniqueId($unique_id, DueInvoiceService $service)
    {
        $data = $service->getInvoiceDataByUniqueId($unique_id);
        return view('business::dues.invoice', $data);
    }

}
