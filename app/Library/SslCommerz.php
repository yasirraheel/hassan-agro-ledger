<?php

namespace App\Library;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SslCommerz
{
    public static function redirect_if_payment_success()
    {
        if (Session::has('fund_callback')) {
            return url(Session::get('fund_callback')['success_url']);
        } else {
            return url('payment/success');
        }
    }

    public static function redirect_if_payment_faild()
    {
        if (Session::has('fund_callback')) {
            return url(Session::get('fund_callback')['cancel_url']);
        } else {
            return url('payment/failed');
        }
    }

    public static function make_payment($array)
    {
        $url = $array['mode'] == 'Live'
            ? 'https://securepay.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';

        $callback_url = ['email' => auth()->user()?->email ?? NULL, 'plan_id' => $array['plan_id'], 'business_id' => $array['business_id'], 'gateway_id' => $array['gateway_id'], 'platform' => session('platform')];

        $post_data = [
            'store_id'       => $array['store_id'],
            'store_passwd'   => $array['store_password'],
            'total_amount'   => $array['pay_amount'],
            'currency'       => $array['currency'] ?? 'BDT',
            'tran_id'        => uniqid(),
            'success_url'    => route('sslcommerz.status', $callback_url),
            'fail_url'       => route('sslcommerz.status', $callback_url),
            'cancel_url'     => route('sslcommerz.status', $callback_url),
            'cus_name'       => $array['name'],
            'cus_email'      => $array['email'],
            'cus_add1'       => $array['address'] ?? 'Dhaka',
            'cus_phone'      => $array['phone'] ?? '01700000000',
            'cus_city'      => 'Dhaka',
            'cus_country'      => 'Bangladesh',
            'shipping_method'      => 'NO',
            'product_name'   => $array['billName'],
            'product_category' => 'general',
            'product_profile'  => 'general',
        ];

        try {
            $client = new Client();
            $response = $client->post($url, [
                'form_params' => $post_data,
            ]);

            $responseBody = json_decode($response->getBody(), true);

            if (isset($responseBody['GatewayPageURL'])) {
                return redirect($responseBody['GatewayPageURL']);
            }

            return response()->json(['error' => $responseBody['failedreason'] ?? 'Payment failed'], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function status(Request $request)
    {
        if ($request->email) {
            $user = User::where('email', request('email'))->first();
            Auth::login($user);
        }

        $status  = $request->status;
        $tran_id = $request->tran_id;

        session()->put('plan_id', $request->plan_id);
        session()->put('business_id', $request->business_id);
        session()->put('gateway_id', $request->gateway_id);
        session()->put('platform', $request->platform);

        if (!$tran_id || !$status) {
            session()->put('payment_msg', 'Invalid callback data');
            return redirect(self::redirect_if_payment_faild());
        }

        try {

            if ($status == 'VALID') {
                return redirect(self::redirect_if_payment_success());
            }

            session()->put('payment_msg', $validationData['error'] ?? 'Transaction not valid');
            return redirect(self::redirect_if_payment_faild());

        } catch (\Exception $e) {
            session()->put('payment_msg', $e->getMessage());
            return redirect(self::redirect_if_payment_faild());
        }
    }
}
