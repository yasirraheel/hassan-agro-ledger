<?php

namespace App\Services;

class ModuleSubscriptionService
{
    public function isModuleEnabled(string $module, ?int $businessId = null): bool
    {
        $planData = plan_data($businessId);

        if (!$planData) {
            return false;
        }

        $modules = $planData->available_modules ?? $planData->plan?->available_modules ?? [];

        if (empty($modules)) {
            return true;
        }

        $value = $modules[$module] ?? false;

        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return ($value['read'] ?? '0') === '1';
        }

        return $value === '1';
    }

    public function getModuleForRoute(string $routeName): ?string
    {
        foreach ($this->routeMap() as $module => $patterns) {
            foreach ($patterns as $pattern) {
                $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $routeName)) {
                    return $module;
                }
            }
        }

        return null;
    }

    private function routeMap(): array
    {
        return [
            'parties' => [
                'business.parties.*',
                'business.sales.store.customer',
                'business.purchases.store.supplier',
            ],
            'sales' => [
                'business.sales.*',
                'business.sale-returns.*',
                'business.view-payment.*',
                'business.delete.payments.*',
            ],
            'purchases' => [
                'business.purchases.*',
                'business.purchase-returns.*',
            ],
            'products' => [
                'business.products.*',
                'business.categories.*',
                'business.brands.*',
                'business.units.*',
                'business.barcodes.*',
                'business.bulk-uploads.*',
                'business.variations.*',
                'business.product-models.*',
                'business.racks.*',
                'business.shelfs.*',
                'business.combo-products.*',
                'business.product.get.shelf.*',
            ],
            'stocks' => [
                'business.stocks.*',
                'business.expired-products.*',
            ],
            'vats' => [
                'business.vats.*',
                'business.vat-groups.*',
            ],
            'dues' => [
                'business.dues.*',
                'business.walk-dues.*',
                'business.party.dues.*',
                'business.collect.dues.*',
                'business.collect.walk.dues.*',
                'business.dues.view-payment.*',
            ],
            'incomes' => [
                'business.incomes.*',
                'business.income-categories.*',
            ],
            'expenses' => [
                'business.expenses.*',
                'business.expense-categories.*',
            ],
            'loss-profits' => [
                'business.loss-profit-history.*',
                'business.loss-profit-reports.*',
                'business.loss-profit-reports.details.*',
                'business.loss-profit.details.reports.filter.*',
            ],
            'payment-types' => [
                'business.banks.*',
                'business.cashes.*',
                'business.cheques.*',
                'business.bank-transactions.*',
                'business.transactions.*',
                'business.day-book-reports.*',
                'business.cash-flow-reports.*',
                'business.balance-sheet.*',
            ],
            'reports' => [
                'business.sale-reports.*',
                'business.sale-return-reports.*',
                'business.purchase-reports.*',
                'business.purchase-return-reports.*',
                'business.vat-reports.*',
                'business.income-reports.*',
                'business.expense-reports.*',
                'business.stock-reports.*',
                'business.due-reports.*',
                'business.supplier-due-reports.*',
                'business.transaction-history-reports.*',
                'business.subscription-reports.*',
                'business.expired-product-reports.*',
                'business.top-product-reports.*',
                'business.product-loss-profit-reports.*',
                'business.discount-product-reports.*',
                'business.combo-product-reports.*',
                'business.product-purchase-reports.*',
                'business.product-sale-reports.*',
                'business.bill-wise-profits.*',
                'business.loss-profit-history-reports.*',
                'business.product-sale-history-reports.*',
                'business.product-purchase-history-reports.*',
                'business.top-customer-reports.*',
                'business.top-supplier-reports.*',
            ],
            'party-reports' => [
                'business.customer-ledger.*',
                'business.supplier-ledger.*',
                'business.party-loss-profit.*',
                'business.top-customers.*',
                'business.top-suppliers.*',
            ],
            'warehouses' => [
                'warehouse.warehouses.*',
            ],
            'transfers' => [
                'business.transfers.*',
            ],
            'branches' => [
                'multibranch.branches.*',
                'business.roles.*',
            ],
            'hrm' => [
                'hrm.*',
            ],
            'sms-marketing' => [
                'business.templates.*',
                'business.sms-gateways.*',
                'business.message-logs.*',
            ],
            'custom-reports' => [
                'business.custom-reports.*',
            ],
        ];
    }
}
