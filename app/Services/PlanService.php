<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanSubscribe;
use Illuminate\Http\Request;

class PlanService
{
    public static function resolveLimits(Request $request): array
    {
        return [
            'allow_multibranch' => $request->allow_multibranch ? 1 : 0,
            'user_limit' => $request->user_unlimited ? -1 : ($request->user_limit ?? 0),
            'product_limit' => $request->product_unlimited ? -1 : ($request->product_limit ?? 0),
            'customer_limit' => $request->customer_unlimited ? -1 : ($request->customer_limit ?? 0),
            'supplier_limit' => $request->supplier_unlimited ? -1 : ($request->supplier_limit ?? 0),
            'warehouse_limit' => $request->warehouse_unlimited ? -1 : ($request->warehouse_limit ?? 0),
            'branch_limit' => $request->allow_multibranch ? ($request->branch_unlimited ? -1 : ($request->branch_limit ?? 0)) : 0,
        ];
    }

    public static function processModules(Request $request): array
    {
        $modules = self::defaultModules();
        foreach ($request->available_modules ?? [] as $module => $value) {
            if (array_key_exists($module, $modules)) {
                $modules[$module] = true;
            }
        }
        return $modules;
    }

    public static function buildSubscriberUpdateData(Request $request, array $modules): array
    {
        return self::resolveLimits($request) + [
            'addon_domain_limit' => $request->addon_domain_limit ?? 0,
            'subdomain_limit' => $request->subdomain_limit ?? 0,
            'available_modules' => $modules,
        ];
    }

    public static function updateActiveSubscribers(Plan $plan, array $data): void
    {
        PlanSubscribe::where('plan_id', $plan->id)
            ->whereIn('id', function ($q) {
                $q->select('plan_subscribe_id')
                    ->from('businesses')
                    ->whereNotNull('plan_subscribe_id')
                    ->where('status', 1);
            })
            ->update($data);
    }

    public static function defaultModules(): array
    {
        return [
            'sales' => false,
            'purchases' => false,
            'products' => false,
            'stocks' => false,
            'parties' => false,
            'finance-accounts' => false,
            'incomes' => false,
            'expenses' => false,
            'vats' => false,
            'dues' => false,
            'loss-profits' => false,
            'payment-types' => false,
            'reports' => false,
            'custom-reports' => false,
            'party-reports' => false,
            'warehouses' => false,
            'transfers' => false,
            'branches' => false,
            'hrm' => false,
            'sms-marketing' => false,
        ];
    }
}
