<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Modules\MarketingAddon\App\Services\NotifyService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function saleMessage($business, $customer, $sale)
    {
        if (!$customer || empty($customer->phone)) {
            return;
        }

        $businessCurrency = business_currency($business->id);

        app(NotifyService::class)->send(
            'new_sale',
            [
                'phone' => $customer->phone,
                'email' => $customer->email,
                'name' => $customer->name,
                'customer_name' => $customer->name,

                'invoice_no' => $sale->invoiceNumber,
                'amount' => currency_format($sale->totalAmount, 'icon', 2, $businessCurrency),
                'date' => formatted_date($sale->saleDate),
                'time' => formatted_time($sale->saleDate),
                'received_amount' => currency_format($sale->paidAmount, 'icon', 2, $businessCurrency),
                'paid_amount' => currency_format($sale->paidAmount, 'icon', 2, $businessCurrency),
                'due_amount' => currency_format($sale->dueAmount, 'icon', 2, $businessCurrency),

                'invoice_url' => route('sale.invoice', ['sale_id' => $sale->id, 'phone' => $customer->phone,]),
                'business_name' => $business->companyName,
                'business_phone' => $business->phoneNumber
            ],
            businessId: $business->id
        );
    }

    public function purchaseMessage($business, $supplier, $purchase)
    {
        if (!$supplier || empty($supplier->phone)) {
            return;
        }

        app(NotifyService::class)->send(
            'purchase',
            [
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'name' => $supplier->name,
                'supplier_name' => $supplier->name,

                'invoice_no' => $purchase->invoiceNumber,
                'amount' => currency_format($purchase->totalAmount),
                'date' => formatted_date($purchase->purchaseDate),
                'time' => formatted_time($purchase->purchaseDate),

                'received_amount' => currency_format(0),
                'paid_amount' => currency_format($purchase->paidAmount),
                'due_amount' => currency_format($purchase->dueAmount),
                'invoice_url' => route('purchase.invoice', ['purchase_id' => $purchase->id, 'phone' => $supplier->phone,]),
                'business_name' => $business->companyName,
                'business_phone' => $business->phoneNumber,
            ],
            businessId: $business->id
        );

    }

    public function dueTransactionMessage($business, $party, $data)
    {
        if (!$party || empty($party->phone)) {
            return;
        }

        $template_name = $party->type == 'Supplier' ? 'payment_paid' : 'payment_received';

        app(NotifyService::class)->send(
            $template_name,
            [
                'phone' => $party->phone,
                'email' => $party->email,
                'name' => $party->name,
                'customer_name' => $party->name,

                'invoice_no' => $data->invoiceNumber,
                'amount' => currency_format($data->payDueAmount),
                'date' => formatted_date($data->paymentDate),
                'time' => formatted_time($data->paymentDate),

                'received_amount' => $party->type == 'Supplier' ? currency_format(0) : $data->payDueAmount,
                'paid_amount' => $party->type == 'Supplier' ? $data->payDueAmount : currency_format(0),
                'due_amount' => currency_format($party->due),

                'invoice_url' => route('due.invoice', ['party_id' => $party->id, 'phone' => $party->phone,]),
                'business_name' => $business->companyName,
                'business_phone' => $business->phoneNumber,
            ],
            businessId: $business->id
        );
    }

    public function paymentReminderMessage($business, $party)
    {
        if (!$party) {
            return;
        }

        app(NotifyService::class)->send(
            'payment_reminder',
            [
                'phone' => $party->phone,
                'email' => $party->email,
                'name' => $party->name,
                'customer_name' => $party->name,
                'invoice_no' => 'NONE',

                'amount' => currency_format($party->due),
                'date' => formatted_date(now()),
                'time' => formatted_time(now()),

                'received_amount' => currency_format(0),
                'paid_amount' => currency_format(0),
                'due_amount' => currency_format(0),

                'invoice_url' => url('/'),
                'business_name' => $business->companyName,
                'business_phone' => $business->phoneNumber,
            ],
            businessId: $business->id
        );
    }

    public function attendanceMessage($business, $attendance, $employee)
    {
        app(NotifyService::class)->send(
            'attendance',
            [
                'phone' => $employee->phone,
                'email' => $employee->email,
                'employee_name' => $employee->name,
                'name' => $employee->name,
                'invoice_no' => 'NONE',

                'amount' => currency_format(0),
                'date' => formatted_date($attendance->date),
                'entry_time' => formatted_time($attendance->time_in),
                'exit_time' => formatted_time($attendance->time_out),
                'time' => formatted_time(now()),

                'received_amount' => currency_format(0),
                'paid_amount' => currency_format(0),
                'due_amount' => currency_format(0),

                'invoice_url' => url('/'),
                'business_name' => $business->companyName,
                'business_phone' => $business->phoneNumber,
            ],
            businessId: $business->id
        );
    }

    protected $message =[
        //response success ..0
        [
            'message'=>'Response Successfully!',
            'alert-type'=>'success'
        ],
        //created ..1
        [
            'message'=>'Data Created Successfully!',
            'alert-type'=>'success'
        ],
        // data found ..2
        [
            'message'=>'Get Data Successfully!',
            'alert-type'=>'success'
        ],
        //  data updated ..3
        [
            'message'=>'Data Updated Successfully!',
            'alert-type'=>'success'
        ],
        //  validator ..4
        [
            'message'=>'Sorry!!Data validation Failed!',
            'alert-type'=>'error'
        ],
        //  bad request ..5
        [
            'message'=>'Sorry!! You\'ar Send Bad Request!',
            'alert-type'=>'error'
        ],
        //  data not found ..6
        [
            'message'=>'Sorry!! Data Not Found!',
            'alert-type'=>'error'
        ],
        //  data already exist ..7
        [
            'message'=>'Sorry!! Data Already Exists!',
            'alert-type'=>'error'
        ],
        //  unauthorized request ..8
        [
            'message'=>'Sorry!! Unauthenticated Request!',
            'alert-type'=>'error'
        ],
        //  time request error ..9
        [
            'message'=>'Sorry!! Try After Few Time!',
            'alert-type'=>'error'
        ],
        //  check balance error ..10
        [
            'message'=>'Sorry!! Unavailable Balance!',
            'alert-type'=>'error'
        ],
        //  requested accepted ..11
        [
            'message'=>'Data Request Accepted!',
            'alert-type'=>'success'
        ],
        //  requested rejected ..12
        [
            'message'=>'Data Request Rejected!',
            'alert-type'=>'success'
        ],
        //  requested not modify ..13
        [
            'message'=>'Data Not Modified!',
            'alert-type'=>'warning'
        ],
        //  requested deleted ..14
        [
            'message'=>'Data deleted!',
            'alert-type'=>'success'
        ],
        //  requested deleted ..15
        [
            'message'=>'Bad Request!',
            'alert-type'=>'success'
        ]
    ];

    protected function setSuccess($message)
    {
        session()->flash('type', 'success');
        session()->flash('message', $message);
    }

    protected function setError($message)
    {
        session()->flash('type', 'warning');
        session()->flash('message', $message);
    }

    protected function setDelete($message)
    {
        session()->flash('type', 'warning');
        session()->flash('message', $message);
    }

    protected function respondWithValidatorError($message = '', $data = [], $code = 422)
    {
        return response()->json([
            'error'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }

    protected function respondWithSuccess($message = '', $data = [], $code = 200)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }

    protected function respondWithCreated($message = '', $data = [], $code = 201)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }

    protected function respondWithFound($message = '', $data = [], $code = 302)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }

    protected function respondWithUpdate($message = '', $data = [], $code = 426)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
    protected function respondWithAccept($message = '', $data = [], $code = 202)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
    protected function respondWithReject($message = '', $data = [], $code = 406)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
    protected function respondWithNotModify($message = '', $data = [], $code = 304)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }

    protected function respondWithError($message = '', $data = [], $code = 400)
    {
        return response()->json([
            'error'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }

    protected function respondWithErrorNotFound($message = '', $data = [], $code = 404)
    {
        return response()->json([
            'error'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }

    protected function respondWithAlreadyExists($message = '', $data = [], $code = 208)
    {
        return response()->json([
            'error'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }

    protected function respondWithUnauthorized($message = '', $data = [], $code = 401)
    {
        return response()->json([
            'error'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
    protected function respondWithNotAcceptable($message = '', $data = [], $code = 406)
    {
        return response()->json([
            'error'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
    protected function respondWithUnavilableBalance($message = '', $data = [], $code = 451)
    {
        return response()->json([
            'error'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
    protected function respondWithDelete($message = '', $data = [], $code = 204)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
    protected function respondWithBadRequest($message = '', $data = [], $code = 400)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
    protected function respondWithNotExtanded($message = '', $data = [], $code = 510)
    {
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data
        ],$code);
    }
}
