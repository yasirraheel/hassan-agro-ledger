<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = array(
            array('name' => 'Stripe','currency_id' => 2,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"stripe_key":"your_stripe_key","stripe_secret":"your_stripe_secret"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\StripeGateway', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'mollie','currency_id' => 2,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"api_key":"your_mollie_api_key"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Mollie', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'paypal','currency_id' => 2,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"client_id":"your_paypal_client_id","client_secret":"your_paypal_client_secret"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Paypal', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'paystack','currency_id' => 5,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"public_key":"your_paystack_public_key","secret_key":"your_paystack_secret_key"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Paystack', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'razorpay','currency_id' => 4,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"key_id":"your_razorpay_key_id","key_secret":"your_razorpay_key_secret"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Razorpay', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'instamojo','currency_id' => 4,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"x_api_key":"your_instamojo_api_key","x_auth_token":"your_instamojo_auth_token"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Instamojo', 'phone_required' => 1, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'toyyibpay','currency_id' => 6,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"user_secret_key":"your_toyyibpay_user_secret_key","cateogry_code":"your_category_code"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Toyyibpay', 'phone_required' => 1, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'flutterwave','currency_id' => 5,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"public_key":"your_flutterwave_public_key","secret_key":"your_flutterwave_secret_key","encryption_key":"your_flutterwave_encryption_key","payment_options":"card"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Flutterwave', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'thawani','currency_id' => 7,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"secret_key":"your_thawani_secret_key","publishable_key":"your_thawani_publishable_key"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Thawani', 'phone_required' => 1, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'mercadopago','currency_id' => 2,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"secret_key":"your_mercadopago_secret_key","public_key":"your_mercadopago_public_key"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Mercado', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'phonepe','currency_id' => 4,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"key_id":"your_phonepe_key_id","key_secret":"your_phonepe_key_secret"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\PhonePe', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'paytm','currency_id' => 4,'mode' => '1','status' => '1','charge' => '2','image' => NULL,'data' => '{"merchant_id":"your_paytm_merchant_id","merchant_key":"your_paytm_merchant_key","merchant_website":"WEBSTAGING","channel":"WEB","industry_type":"Retail","environment":"local"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\Paytm', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:54:44'),

            array('name' => 'Sslcommerz','currency_id' => 1,'mode' => '1','status' => '1','charge' => '1','image' => NULL,'data' => '{"store_id":"your_sslcommerz_store_id","store_password":"your_sslcommerz_store_password"}','manual_data' => NULL,'is_manual' => '0','accept_img' => '0','namespace' => 'App\\Library\\SslCommerz', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 17:45:52'),

            array('name' => 'Manual','currency_id' => 2,'mode' => '1','status' => '1','charge' => '0','image' => NULL,'data' => '','manual_data' => '{"label":["Bank Name","Transaction ID"],"is_required":["1","1"]}','is_manual' => '1','accept_img' => '1','namespace' => 'App\\Library\\StripeGateway', 'phone_required' => 0, 'instructions' => NULL, 'created_at' => '2024-02-18 17:45:52','updated_at' => '2024-02-18 18:24:39'),
        );

        Gateway::insert($gateways);
    }
}
