<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PlanLimitService
{
    public static function check(string $limitField, string $modelClass, ?int $businessId = null): void
    {
        $businessId ??= optional(auth()->user())->business_id;

        if (!$businessId) {
            return;
        }

        if (!check_plan_limit_by_model($limitField, $modelClass, $businessId)) {
            throw ValidationException::withMessages([
                $limitField => __('You have reached the maximum :limit limit for your plan.', [
                    'limit' => str_replace('_limit', '', $limitField),
                ]),
            ]);
        }
    }
}
