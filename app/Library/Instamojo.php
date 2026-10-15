<?php

namespace App\Library;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class Instamojo
{
    public static function redirect_if_payment_success()
    {
        if (Session::has('fund_callback')) {
            return url(Session::get('fund_callback')['success_url']);
        }
        return url('payment/success');
    }

    public static function redirect_if_payment_faild()
    {
        if (Session::has('fund_callback')) {
            return url(Session::get('fund_callback')['cancel_url']);
        }
        return url('payment/failed');
    }

    public static function fallback()
    {
        return url('payment/instamojo');
    }

    public static function make_payment($array)
    {
        $regex = "/^(0|91|\+91)?[789]\d{9}$/";

        if (empty($array['phone'])) {
            return back()->with('warning', 'Phone Number not given!');
        }

        if (!preg_match($regex, $array['phone'])) {
            return back()->with('warning', 'Phone Number not valid!');
        }

        $mode = $array['mode'];

        // IMPORTANT: use correct base URL
        $baseUrl = $mode === 'Sandbox'
            ? 'https://test.instamojo.com'
            : 'https://www.instamojo.com';

        $url = $baseUrl . '/api/1.1/payment-requests/';

        try {

            $response = Http::asForm()
                ->withHeaders([
                    'X-Api-Key' => $array['x_api_key'],
                    'X-Auth-Token' => $array['x_auth_token'],
                ])
                ->post($url, [
                    'purpose' => $array['billName'],
                    'amount' => $array['pay_amount'],
                    'buyer_name' => $array['name'],
                    'email' => $array['email'],
                    'phone' => $array['phone'],
                    'redirect_url' => self::fallback(),
                    'send_email' => true,
                    'send_sms' => true,
                    'allow_repeated_payments' => false,
                ]);

            if (!$response->successful()) {
                throw new \Exception($response->body());
            }

            $body = $response->json();

            if (!isset($body['payment_request']['longurl'])) {
                throw new \Exception('Invalid Instamojo response');
            }

            Session::put('instamojo_credentials', [
                'gateway_id' => $array['gateway_id'],
                'amount' => $array['amount'],
                'charge' => $array['charge'],
                'mode' => $mode,
                'x_api_key' => $array['x_api_key'],
                'x_auth_token' => $array['x_auth_token'],
            ]);

            return redirect($body['payment_request']['longurl']);

        } catch (\Throwable $e) {

            Session::flash('error', $e->getMessage());
            return redirect(self::redirect_if_payment_faild());
        }
    }

    public function status()
    {
        if (!Session::has('instamojo_credentials')) {
            abort(404);
        }

        $response = request()->all();
        $info = Session::get('instamojo_credentials');

        if (isset($response['payment_status']) && $response['payment_status'] === 'Credit') {

            $data = [
                'payment_id' => $response['payment_id'] ?? null,
                'payment_method' => 'instamojo',
                'gateway_id' => $info['gateway_id'],
                'amount' => $info['amount'],
                'charge' => $info['charge'],
                'status' => 1,
                'payment_status' => 1,
            ];

            Session::forget('instamojo_credentials');
            Session::put('payment_info', $data);

            return redirect(self::redirect_if_payment_success());
        }

        Session::forget('instamojo_credentials');
        return redirect(self::redirect_if_payment_faild());
    }

    public static function isfraud($creds)
    {
        $mode = $creds['mode'];

        $baseUrl = $mode === 'Sandbox'
            ? 'https://test.instamojo.com'
            : 'https://www.instamojo.com';

        $url = $baseUrl . '/api/1.1/payments/' . $creds['payment_id'] . '/';

        try {

            $response = Http::withHeaders([
                'X-Api-Key' => $creds['x_api_key'],
                'X-Auth-Token' => $creds['x_auth_token'],
            ])->get($url);

            if ($response->successful()) {
                $body = $response->json();
                return isset($body['payment']['status']) &&
                       $body['payment']['status'] === 'Credit' ? 1 : 0;
            }

            return 0;

        } catch (\Throwable $e) {
            return 0;
        }
    }
}