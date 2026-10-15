<?php

use App\Http\Controllers as Web;
use Illuminate\Support\Facades\Route;

Route::get('/save', function () {

    \App\Models\Language::truncate();

    \Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => \Database\Seeders\LanguageSeeder::class,
    ]);

    return 'Seeder executed successfully.';

});

Route::get('/', [Web\WebController::class, 'index'])->name('home');
Route::resource('blogs', Web\BlogController::class)->only('index', 'show', 'store');
Route::get('/about-us', [Web\AboutController::class, 'index'])->name('about.index');
Route::get('/plans', [Web\PlanController::class, 'index'])->name('plan.index');

// Business Signup
Route::get('/get-business-categories', [Web\AcnooBusinessController::class, 'getBusinessCategories'])->name('get-business-categories');
Route::post('/businesses', [Web\AcnooBusinessController::class, 'store'])->name('business.store');
Route::post('/verify-code', [Web\AcnooBusinessController::class, 'verifyCode'])->name('business.verify-code');

Route::get('/data-deletion', [Web\DataDeletionController::class, 'index'])->name('term.index');
Route::get('/terms-conditions', [Web\TermServiceController::class, 'index'])->name('term.index');
Route::get('/privacy-policy', [Web\PolicyController::class, 'index'])->name('policy.index');

Route::get('/contact-us', [Web\ContactController::class, 'index'])->name('contact.index');
Route::post('/contact/store', [Web\ContactController::class, 'store'])->name('contact.store');

// Payment Routes Start
Route::get('/payments-gateways/{plan_id}/{business_id}', [Web\PaymentController::class, 'index'])->name('payments-gateways.index');
Route::post('/payments/{plan_id}/{gateway_id}', [Web\PaymentController::class, 'payment'])->name('payments-gateways.payment');
Route::get('/payment/success', [Web\PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/failed', [Web\PaymentController::class, 'failed'])->name('payment.failed');
Route::post('ssl-commerz/payment/success', [Web\PaymentController::class, 'sslCommerzSuccess']);
Route::post('ssl-commerz/payment/failed', [Web\PaymentController::class, 'sslCommerzFailed']);
Route::get('/order-status', [Web\PaymentController::class, 'orderStatus'])->name('order.status');

// Route for message
Route::get('/sale-invoice/{unique_id}', [Web\AcnooInvoiceController::class, 'saleInvoiceByUniqueId'])->name('sale.invoice.unique');
Route::get('/customer-invoice/{sale_id}/{phone}', [Web\AcnooInvoiceController::class, 'saleInvoice'])->name('sale.invoice');

Route::get('/purchase-invoice/{unique_id}', [Web\AcnooInvoiceController::class, 'purchaseInvoiceByUniqueId'])->name('purchase.invoice.unique');
Route::get('/supplier-invoice/{purchase_id}/{phone}', [Web\AcnooInvoiceController::class, 'purchaseInvoice'])->name('purchase.invoice');

Route::get('/landing-invoice/{party_id}/{phone}', [Web\AcnooInvoiceController::class, 'dueInvoice'])->name('due.invoice');
Route::get('/due-invoice/{unique_id}', [Web\AcnooInvoiceController::class, 'dueInvoiceByUniqueId'])->name('due.invoice.unique');

Route::group([
    'namespace' => 'App\Library',
], function () {
    Route::get('/payment/paypal', 'Paypal@status');
    Route::get('/payment/mollie', 'Mollie@status');
    Route::post('/payment/paystack', 'Paystack@status')->name('paystack.status');
    Route::get('/paystack', 'Paystack@view')->name('paystack.view');
    Route::get('/razorpay/payment', 'Razorpay@view')->name('razorpay.view');
    Route::post('/razorpay/status', 'Razorpay@status');
    Route::get('/mercadopago/pay', 'Mercado@status')->name('mercadopago.status');
    Route::get('/payment/flutterwave', 'Flutterwave@status');
    Route::get('/payment/thawani', 'Thawani@status');
    Route::get('/payment/instamojo', 'Instamojo@status');
    Route::get('/payment/toyyibpay', 'Toyyibpay@status');
    Route::get('/phonepe/status', 'PhonePe@status')->name('phonepe.status');
    Route::post('/paytm/status', 'Paytm@status')->name('paytm.status');
    Route::get('/tap-payment/status', 'TapPayment@status')->name('tap-payment.status');
    Route::post('/sslcommerz/status', 'SslCommerz@status')->name('sslcommerz.status');
});
// Google Drive OAuth for Backup
Route::get('/google-drive/connect', [App\Http\Controllers\Admin\BackupController::class, 'googleDriveConnect'])->name('google-drive.connect');
Route::get('/google-drive/callback', [App\Http\Controllers\Admin\BackupController::class, 'googleDriveCallback'])->name('google-drive.callback');

// Payment Routes End

require __DIR__ . '/auth.php';
require __DIR__ . '/maintenance.php';
