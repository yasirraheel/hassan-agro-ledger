<?php

use App\Models\Branch;
use App\Models\Business;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Option;
use App\Models\PlanSubscribe;
use App\Models\ProductSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCurrency;
use App\Notifications\SendNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Modules\AttendanceDeviceAddon\App\Library\ZKLib;
use Modules\HrmAddon\App\Models\Attendance;
use Modules\HrmAddon\App\Models\Employee;
use Modules\MarketingAddon\App\Models\Smsgateway;
use Nwidart\Modules\Facades\Module;

function expectedItemId(): string
{
    return 53621221;
}

function cache_remember(string $key, callable $callback, int $ttl = 5000): mixed
{
    return cache()->remember($key, env('CACHE_LIFETIME', $ttl), $callback);
}

function get_option(string $key)
{
    return cache_remember($key, function () use ($key) {
        return Option::where('key', $key)->first()->value ?? [];
    });
}

function invoice_setting($business_id = null)
{
    $business_id = $business_id ?? auth()->user()?->business_id;

    return get_option('invoice_setting_' . $business_id);
}

function invoice_language()
{
    return get_option('invoice_language_' . auth()->user()->business_id);
}

function profile_printer()
{
    return get_option('profile_printer_' . auth()->user()->business_id);
}

function product_setting($business_id = null)
{
    $businessId = $business_id ?? auth()->user()?->business_id;
    $cacheKey = 'product_setting_' . $businessId;

    return cache()->remember($cacheKey, 60, function () use ($businessId) {
        $productSetting = ProductSetting::where('business_id', $businessId)->first();

        if ($productSetting) {
            $productSetting->modules = $productSetting->modules ?? [];
        }

        return $productSetting;
    });
}

function is_module_enabled(?array $modules, string $key): bool
{
    // Keys that should default to true if not set
    $defaultTrueKeys = [
        'show_product_category',
        'show_product_stock',
        'show_exclusive_price',
        'show_inclusive_price',
        'show_profit_percent',
        'show_product_sale_price',
    ];

    if (in_array($key, $defaultTrueKeys)) {
        return !isset($modules[$key]) || (bool)$modules[$key];
    }

    // All other keys: show only if explicitly set to true
    return isset($modules[$key]) && (bool)$modules[$key];
}

function formatted_date(?string $date = null, string $format = 'd M, Y'): ?string
{
    return !empty($date) ? Date::parse($date)->format($format) : null;
}

function formatted_time(?string $time = null, string $format = 'h:i A'): ?string
{
    return !empty($time) ? Date::parse($time)->format($format) : null;
}

function formatted_datetime(?string $datetime = null, string $format = 'd M, Y h:i A'): ?string
{
    return !empty($datetime) ? Date::parse($datetime)->format($format) : null;
}

function sendNotification($id, $url, $message, $user = null)
{
    $notify = [
        'id' => $id,
        'url' => $url,
        'user' => $user,
        'message' => $message,
    ];

    $notify_user = User::where('role', 'superadmin')->first();
    Notification::send($notify_user, new SendNotification($notify));
}

function sendNotifyToUser($id, $url, $message, $user, $required_permission = null)
{
    $notify = [
        'id' => $id,
        'url' => $url,
        'user' => $user,
        'message' => $message,
        'required_permission' => $required_permission,
    ];

    $notify_users = User::where('business_id', $user)->get();
    Notification::send($notify_users, new SendNotification($notify));
}

function currency_format($amount, $type = "icon", $decimals = 2, $currency = null, $abbreviate = false, $apply_rounding = false)
{
    $currency = $currency ?? default_currency();

    if ($apply_rounding) {
        $amount = sale_rounding((float)$amount);
    }

    if ($abbreviate) {
        $amount = format_number($amount, $decimals);
    } else {
        $has_fraction = $amount != floor($amount);
        $amount = $has_fraction ? number_format($amount, $decimals) : number_format($amount, 0);
    }

    if ($type == "icon" || $type == "symbol") {
        if ($currency->position == "right") {
            return $amount . $currency->symbol;
        } else {
            return $currency->symbol . $amount;
        }
    } else {
        if ($currency->position == "right") {
            return $amount . ' ' . $currency->code;
        } else {
            return $currency->code . ' ' . $amount;
        }
    }
}

function format_number(float|int $number, int $decimals = 2): string
{
    if ($number >= 1e9) {
        return remove_trailing_zeros($number / 1e9, $decimals) . "B";
    } elseif ($number >= 1e6) {
        return remove_trailing_zeros($number / 1e6, $decimals) . "M";
    } elseif ($number >= 1e3) {
        return remove_trailing_zeros($number / 1e3, $decimals) . "K";
    } else {
        return remove_trailing_zeros($number, $decimals);
    }
}

function remove_trailing_zeros(float|int $number, int $decimals = 2): string
{
    return rtrim(rtrim(number_format($number, $decimals, '.', ''), '0'), '.');
}

function amountInWords(float $amount, int $decimals = 2, ?int $business_id = null): string
{
    if (!extension_loaded('intl')) {
        return '';
    }

    $has_fraction = fmod($amount, 1) != 0;
    $amount = $has_fraction ? round($amount, $decimals) : round($amount);

    $formatter = new \NumberFormatter('en_US', \NumberFormatter::SPELLOUT);
    $words = $formatter->format($amount);

    $business_currency = business_currency($business_id);

    return $words . ' ' . ($business_currency->name ?? '');
}

function convert_money($amount, $currency)
{
    if ($currency->code == default_currency('code') || $amount == 0) {
        return round($amount, 2);
    } else {
        $default_currency_rate = default_currency()->rate ?? 1;
        return $amount * $currency->rate / $default_currency_rate;
    }
}

function default_currency($key = null, ? Currency $currency = null): object|int|string
{
    $currency = $currency ?? cache_remember('default_currency', function () {
        $currency = Currency::whereIsDefault(1)->first();

        if (!$currency) {
            $currency = (object)['name' => 'US Dollar', 'code' => 'USD', 'rate' => 1, 'symbol' => '$', 'position' => 'left', 'status' => true, 'is_default' => true,];
        }

        return $currency;
    });

    return $key ? $currency->$key : $currency;
}

function paymentReminderMessage($sale, $business, $customer_name = 'Customer')
{
    $message = get_option('sms-templates-' . $business->id)['purchase_sms'] ?? "Hello [customer_name], kindly clear your outstanding balance of [amount]. For details, contact us at [business_phone].";

    $data = [
        'customer_name' => $customer_name,
        'invoice_no' => $sale->invoiceNumber,
        'due_amount' => $sale->totalAmount,
        'date' => formatted_date($sale->saleDate),
        'business_name' => $business->companyName,
        'business_phone' => $business->phoneNumber,
    ];

    foreach ($data as $key => $value) {
        $message = str_replace("[$key]", $value, $message);
    }

    return $message;
}

function sendMessage($numbers, $contents, $business_id = null)
{
    $gateways = Smsgateway::where('business_id', $business_id ?? auth()->user()->business_id)->where('status', 1)->get();

    foreach ($gateways as $gateway) {
        $gateway->namespace::send_message($gateway, $numbers, $contents);
        session()->put('gateway_id', $gateway->id);

        return [
            'status' => true,
            'message' => "Message send successfully."
        ];
    }
}

function restorePublicImages()
{
    if (!env('DEMO_MODE')) {
        return true;
    }

    DB::table('sales')->where('business_id', 1)->delete();
    DB::table('sale_returns')->where('business_id', 1)->delete();
    DB::table('purchases')->where('business_id', 1)->delete();
    DB::table('purchase_returns')->where('business_id', 1)->delete();
    DB::table('due_collects')->where('business_id', 1)->delete();
    DB::table('parties')->where('business_id', 1)->delete();
    DB::table('expense_categories')->where('business_id', 1)->delete();
    DB::table('income_categories')->where('business_id', 1)->delete();

    Artisan::call('db:seed', ['--class' => 'DemoSeeder']);
}

if (!function_exists('formatTimeToWords')) {
    function formatTimeToWords(string|null $time): string
    {
        if (empty($time)) {
            return '0';
        }

        if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            return '0';
        }

        $parts = explode(':', $time);

        $hours = isset($parts[0]) ? (int)$parts[0] : 0;
        $minutes = isset($parts[1]) ? (int)$parts[1] : 0;

        $hourString = $hours == 1 ? 'hour' : 'hours';
        $minuteString = $minutes == 1 ? 'minute' : 'minutes';

        $formattedTime = [];

        if ($hours > 0) {
            $formattedTime[] = "$hours $hourString";
        }

        if ($minutes > 0) {
            $formattedTime[] = "$minutes $minuteString";
        }

        return empty($formattedTime) ? '0' : implode(' and ', $formattedTime);
    }
}

function languages()
{
    return cache_remember('languages', function () {
        return Language::orderBy('order', 'asc')->where('status', 1)->get();
    });
}

// BUSINESS PANEL

// user role permission
if (!function_exists('visible_permission')) {
    function visible_permission($permission)
    {
        $user = auth()->user();

        // Ensure the user is authenticated and has a business_id
        if (!$user || !$user->business_id) {
            return false;
        }

        // Handle visibility field directly as an array or decode it if it's a string
        $permissions = is_array($user->visibility)
            ? $user->visibility
            : json_decode($user->visibility, true);

        return $permissions[$permission] ?? false;
    }
}

function get_business_option($key, $business_id = null)
{
    $business_id = $business_id ?? auth()->user()?->business_id;

    if (!$business_id) {
        return null;
    }

    $cacheKey = "business_setting_" . $business_id;

    return Cache::remember($cacheKey, now()->addDay(), function () use ($key, $business_id) {

        if ($key === 'business-settings') {
            return Option::where('key', 'business-settings')
                ->whereJsonContains('value->business_id', $business_id)
                ->first()
                ->value ?? null;
        }

        return null;
    });
}

function plan_data($business_id = null)
{
    $business_id = $business_id ?? auth()->user()->business_id;

    return cache_remember('plan-data-' . $business_id, function () use ($business_id) {
        $planSubscribe = PlanSubscribe::with('plan:id,subscriptionName')->where('business_id', $business_id)->latest()->first();

        if ($planSubscribe) {
            $business = Business::findOrFail($planSubscribe->business_id);
            $planSubscribe->will_expire = $business->will_expire;
        }
        return $planSubscribe;
    });
}

function branch_count()
{
    $business_id = auth()->user()->business_id;

    return cache_remember('branch-count-' . $business_id, function () use ($business_id) {
        $totalBranch = Branch::where('business_id', $business_id)->count();

        return $totalBranch;
    });
}

function multibranch_active()
{
    return plan_data()['allow_multibranch'] ?? false;
}

function business_currency($business_id = null)
{
    $business_id = $business_id ?? auth()->user()->business_id;

    return cache_remember("business_currency_{$business_id}", function () use ($business_id) {
        $businessCurrency = UserCurrency::where('business_id', $business_id)->first() ?? Currency::where('is_default', 1)->first();;

        if ($businessCurrency) {
            return (object)[
                'name' => $businessCurrency->name,
                'rate' => $businessCurrency->rate,
                'code' => $businessCurrency->code,
                'symbol' => $businessCurrency->symbol,
                'position' => $businessCurrency->position,
            ];
        }

        return default_currency();
    });
}

function sale_rounding(?float $amount = null, ?string $round_option = null): float|string
{
    $business_id = auth()->user()->business_id;

    // If $round_option is not passed, try to fetch from settings
    if (is_null($round_option)) {
        $round_option = cache_remember("business_sale_rounding_{$business_id}", function () use ($business_id) {
            return Option::where('key', 'business-settings')
                ->whereJsonContains('value->business_id', $business_id)
                ->first()
                ->value['sale_rounding_option'] ?? 'none';
        });
    }

    if (is_null($amount)) {
        return $round_option;
    }

    // Apply rounding if amount is provided
    return match ($round_option) {
        'round_up' => ceil($amount),
        'nearest_whole_number' => round($amount),
        'nearest_0.05' => round($amount * 20) / 20,
        'nearest_0.1' => round($amount * 10) / 10,
        'nearest_0.5' => round($amount * 2) / 2,
        default => $amount,
    };
}

function formatBytes(int|float $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;
    
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}

function moduleCheck($module)
{
    $module = Module::find($module);

    if ($module && $module->isEnabled()) {
        return true;
    }

    return false;
}

function plan_module_enabled(string $module): bool
{
    return app(\App\Services\ModuleSubscriptionService::class)->isModuleEnabled($module);
}


function remaining_days($date)
{
    $today = \Carbon\Carbon::today();
    $expiry = \Carbon\Carbon::parse($date);
    $diff = $today->diffInDays($expiry, false);

    return $diff > 0 ? "$diff days" : "";
}

// update RemainingBalance
function updateBalance($amount, string $type, $branch_id = null)
{
    $amount = is_numeric($amount) ? (float)$amount : 0;
    $businessId = auth()->user()->business_id;

    // if active branch, then update active branch
    $branch = auth()->user()->active_branch;
    if ($branch) {
        if ($type == 'increment') {
            $branch->increment('branchRemainingBalance', $amount);
        } elseif ($type == 'decrement') {
            $branch->decrement('branchRemainingBalance', $amount);
        }
        return;
    }

    //If branch_id is provided, update that branch
    if ($branch_id) {
        $branch = Branch::find($branch_id);
        if ($branch) {
            if ($type == 'increment') {
                $branch->increment('branchRemainingBalance', $amount);
            } elseif ($type == 'decrement') {
                $branch->decrement('branchRemainingBalance', $amount);
            }
        }
        return;
    }

    // If no branch, update business balance
    $business = Business::find($businessId);
    if ($business) {
        if ($type == 'increment') {
            $business->increment('remainingShopBalance', $amount);
        } elseif ($type == 'decrement') {
            $business->decrement('remainingShopBalance', $amount);
        }
    }
}

function manipulateBranchData($business_id)
{
    $business = auth()->user()->business;
    $shop_owner = User::where(['business_id' => $business_id, 'role' => 'shop-owner'])->firstOrFail();

    $branch = Branch::create([
        'is_main' => 1,
        'email' => $shop_owner->email,
        'name' => $business->companyName,
        'phone' => $business->phoneNumber,
        'address' => $business->address
    ]);

    $updates = [
        'users'            => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id, 'role' => 'staff']],
        'stocks'           => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'product_settings' => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'sale_returns'     => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'purchase_returns' => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'expenses'         => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'incomes'          => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'sales'            => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'purchases'        => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'due_collects'     => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'parties'          => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
        'transactions'     => ['branch_id' => $branch->id, 'where' => ['business_id' => $business_id]],
    ];

    foreach ($updates as $table => $data) {
        $query = DB::table($table);
        if (!empty($data['where'])) {
            $query->where($data['where']);
            unset($data['where']);
        }
        $query->update($data);
    }

    // combo_products has no business_id column; scope via stock_id
    DB::table('combo_products')
        ->whereNull('branch_id')
        ->whereIn('stock_id', function ($q) use ($business_id) {
            $q->select('id')->from('stocks')->where('business_id', $business_id);
        })
        ->update(['branch_id' => $branch->id]);

    if (moduleCheck('HrmAddon')) {
        DB::table('holidays')->where('business_id', $business_id)->update(['branch_id' => $branch->id]);
        DB::table('attendances')->where('business_id', $business_id)->update(['branch_id' => $branch->id]);
        DB::table('leaves')->where('business_id', $business_id)->update(['branch_id' => $branch->id]);
        DB::table('payrolls')->where('business_id', $business_id)->update(['branch_id' => $branch->id]);
        DB::table('employees')->where('business_id', $business_id)->update(['branch_id' => $branch->id]);
    }

    if (moduleCheck('WarehouseAddon')) {
        DB::table('warehouses')->where('business_id', $business_id)->update(['branch_id' => $branch->id]);
        DB::table('transfers')->where('business_id', $business_id)->update([
            'from_branch_id' => $branch->id,
            'to_branch_id'   => $branch->id
        ]);
    }

    return true;
}

function get_root_domain()
{
    $appUrl = config('app.url');
    return parse_url($appUrl, PHP_URL_HOST);
}

function checkDomainStatus($domain)
{
    $result = [
        'domain' => $domain,
        'exists' => false,
        'http'   => false,
        'https'  => false,
    ];

    // 1. Check if domain resolves (DNS record exists)
    if (dns_get_record($domain, DNS_A) || dns_get_record($domain, DNS_AAAA)) {
        $result['exists'] = true;

        // 2. Check HTTP (port 80)
        try {
            $response = Http::timeout(5)->get("http://{$domain}");
            if ($response->successful()) {
                $result['http'] = true;
            }
        } catch (\Exception $e) {
            $result['http'] = false;
        }

        // 3. Check HTTPS (port 443)
        try {
            $response = Http::timeout(5)->get("https://{$domain}");
            if ($response->successful()) {
                $result['https'] = true;
            }
        } catch (\Exception $e) {
            $result['https'] = false;
        }
    }

    return $result;
}

function custom_reports()
{
    $business_id = auth()->user()->business_id;

    if (moduleCheck('CustomReportsAddon')) {
        return cache_remember('custom-reports-' . $business_id, function () use ($business_id) {
            return Modules\CustomReportsAddon\App\Models\CustomReport::where('business_id', auth()->user()->business_id)->where('status', 1)->get();
        });
    } else {
        return [];
    }
}

function cash_balance()
{
    $businessId = auth()->user()->business_id;

    if (!$businessId) {
        return 0;
    }

    // Sum of all credits and bank_to_cash
    $totalCredit = Transaction::where('business_id', $businessId)
        ->where(function ($q) {
            $q->where('type', 'credit')
                ->orWhere('transaction_type', 'bank_to_cash');
        })
        ->sum('amount');

    // Sum of all debits and cash_to_bank
    $totalDebit = Transaction::where('business_id', $businessId)
        ->where(function ($q) {
            $q->where('type', 'debit')
                ->orWhere('transaction_type', 'cash_to_bank');
        })
        ->sum('amount');

    return $totalCredit - $totalDebit;
}

function transaction_types($transactions): string
{
    if (!$transactions) {
        return '';
    }

    return $transactions
        ->map(function ($transaction) {
            if (
                $transaction->transaction_type === 'bank_payment' &&
                !empty($transaction->paymentType?->name)
            ) {
                return $transaction->paymentType->name;
            }

            return $transaction->transaction_type
                ? ucfirst(explode('_', $transaction->transaction_type)[0])
                : '';
        })
        ->filter()
        ->unique()
        ->implode(', ');
}

function extractTemplateVariables(string $content): array
{
    preg_match_all('/\[\s*([a-zA-Z0-9_]+)\s*\]/', $content, $matches);

    return array_values(array_unique($matches[1]));
}

function safeDropForeignByName($table, $foreign)
{
    try {
        DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$foreign`");
    } catch (\Throwable $e) {
        // ignore
    }
}

function sync_attendance_device_data($devices)
{
    $ATTEN = array(
        0 => "Check In",
        1 => "Check Out",
        2 => "Break In",
        3 => "Break Out",
        4 => "Overtime In",
        5 => "Overtime Out",
    );

    foreach ($devices as $device) {
        $zk = new ZKLib($device->ip, $device->port);
        if ($zk->connect()) {
            $device->serial_number = str_replace('~SerialNumber=', '', $zk->serialNumber());

            $users = $zk->getUser();
            $device->total_user = count($users);

            $attendances = $zk->getAttendance();
            $device->total_attendance = count($attendances);

            foreach ($attendances as $item) {
                $employee = Employee::with('shift')->where('zkteco_uid', $item[1])->first();
                if (!$employee) {
                    continue;
                }

                // $dutyStart = new DateTime('1970-01-01 ' . $employee->shift?->start_time);
                // $checkin = new DateTime('1970-01-01 ' . date('H:i:s', strtotime($item[3])));

                // $lateMinutes = max(0, ($checkin->getTimestamp() - $dutyStart->getTimestamp()) / 60);

                if ($ATTEN[$item[2]] == "Check In") {

                    Attendance::updateOrCreate([
                        'date' => date('Y-m-d', strtotime($item[3])),
                        'employee_id' => $employee->id,
                        'branch_id' => $device->branch_id,
                        'business_id' => $device->business_id,
                    ], [
                        'shift_id' => $employee->shift_id,
                        'time_in' => date('H:i:s', strtotime($item[3])),
                    ]);
                }

                // $dutyEnd = new DateTime('1970-01-01 ' . $employee->shift?->end_time);
                // $checkout = new DateTime('1970-01-01 ' . date('H:i:s', strtotime($item[3])));

                // $earlyLeaveMinutes = max(0, ($dutyEnd->getTimestamp() - $checkout->getTimestamp()) / 60);
                // $overtimeMinutes = max(0, ($checkout->getTimestamp() - $dutyEnd->getTimestamp()) / 60);

                if ($ATTEN[$item[2]] == "Check Out") {

                    $data = Attendance::updateOrCreate([
                        'date' => date('Y-m-d', strtotime($item[3])),
                        'employee_id' => $employee->id,
                        'branch_id' => $device->branch_id,
                        'business_id' => $device->business_id,
                    ], [
                        'shift_id' => $employee->shift_id,
                        'time_out' => date('H:i:s', strtotime($item[3])),
                    ]);

                    if (!$data->wasRecentlyCreated) {
                        $data->status = 'present';
                    }
                }
            }

            $device->last_sync = now();
            $device->save();

            $zk->disconnect();
        } else {
            echo "failed to connect to device ID:" . $device->id . " <br>";
        }
    }
}

if (!function_exists('check_plan_limit_by_model')) {
    function check_plan_limit_by_model(string $limitField, string $modelClass, ?int $businessId = null): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        $businessId ??= auth()->user()->business_id;
        $planData = plan_data($businessId);

        if (!$planData) {
            return false;
        }

        if ($limitField === 'branch_limit' && !multibranch_active()) {
            return false;
        }

        $rawValue = $planData->$limitField ?? null;

        if ($rawValue === null) {
            return true;
        }

        $limit = (int) $rawValue;

        if ($limit === -1) {
            return true;
        }

        if ($limit === 0) {
            return false;
        }

        if ($modelClass === 'customer') {
            $count = \App\Models\Party::where('business_id', $businessId)
                ->whereIn('type', ['Retailer', 'Dealer', 'Wholesaler'])
                ->count();
        } elseif ($modelClass === 'supplier') {
            $count = \App\Models\Party::where('business_id', $businessId)
                ->where('type', 'Supplier')
                ->count();
        } elseif ($modelClass === 'user') {
            $count = \App\Models\User::where('business_id', $businessId)
                ->whereIn('role', ['staff', 'shop-owner'])
                ->count();
        } else {
            $count = $modelClass::where('business_id', $businessId)->count();
        }

        return $count < $limit;
    }
}
