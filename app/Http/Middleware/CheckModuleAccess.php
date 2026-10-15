<?php

namespace App\Http\Middleware;

use App\Services\ModuleSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if ($routeName) {
            $module = app(ModuleSubscriptionService::class)->getModuleForRoute($routeName);

            if ($module && !plan_module_enabled($module)) {
                return $this->deny($request);
            }

            if ($module) {
                return $next($request);
            }
        }

        $module = $this->getModuleFromUrl($request->path());

        if ($module && !plan_module_enabled($module)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    private function getModuleFromUrl(string $path): ?string
    {
        $path = preg_replace('#^api/v\d+/#', '', $path);
        $segment = explode('/', $path)[0] ?? '';

        $map = [
            'banks' => 'payment-types',
            'bank-transactions' => 'payment-types',
            'branches' => 'branches',
            'brands' => 'products',
            'cashes' => 'payment-types',
            'categories' => 'products',
            'cheques' => 'payment-types',
            'departments' => 'hrm',
            'designations' => 'hrm',
            'dues' => 'dues',
            'employees' => 'hrm',
            'expense-categories' => 'expenses',
            'expenses' => 'expenses',
            'exit-branch' => 'branches',
            'holidays' => 'hrm',
            'income-categories' => 'incomes',
            'incomes' => 'incomes',
            'invoices' => 'sales',
            'leave-reports' => 'hrm',
            'leave-types' => 'hrm',
            'leaves' => 'hrm',
            'parties' => 'parties',
            'payment-types' => 'payment-types',
            'payroll-reports' => 'hrm',
            'payrolls' => 'hrm',
            'product-models' => 'products',
            'products' => 'products',
            'purchase' => 'purchases',
            'purchases-return' => 'purchases',
            'racks' => 'products',
            'reports' => 'reports',
            'sales' => 'sales',
            'sales-return' => 'sales',
            'shelfs' => 'products',
            'shifts' => 'hrm',
            'stocks' => 'stocks',
            'switch-branch' => 'branches',
            'transactions' => 'payment-types',
            'transfers' => 'transfers',
            'units' => 'products',
            'variations' => 'products',
            'vats' => 'vats',
            'warehouses' => 'warehouses',
            'warehouses-by-branch' => 'warehouses',
            'sale-taxes' => 'vats',
            'purchase-taxes' => 'vats',
            'attendances' => 'hrm',
            'attendance-reports' => 'hrm',
        ];

        return $map[$segment] ?? null;
    }

    private function deny(Request $request): Response
    {
        $message = __('This module is not included in your subscription plan.');

        return $request->wantsJson()
            ? response()->json(['message' => $message], 400)
            : redirect()->back()->with('error', $message);
    }
}
