<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;

class AppSettingsController extends Controller
{
    public function index()
    {
        $app_settings = Option::where('key', 'app_settings')->first();
        $raw = $app_settings?->value ?? [];

        $splash_logo = !empty($raw['splash_logo']) ? asset($raw['splash_logo']) : null;
        $app_logo = !empty($raw['app_logo']) ? asset($raw['app_logo']) : null;

        $data = [
            'app_name' => $raw['app_name'] ?? 'Business Manager',
            'powered_by' => $raw['powered_by'] ?? 'Business Manager',
            'company_website' => $raw['company_website'] ?? 'https://hassanagro.com',
            'splash_bg_color' => $raw['splash_bg_color'] ?? '#C52127',
            'primary_color' => $raw['primary_color'] ?? '#C52127',
            'app_version' => $raw['app_version'] ?? '1.0.0',
            'support_email' => $raw['support_email'] ?? '',
            'support_phone' => $raw['support_phone'] ?? '',
            'splash_logo_url' => $splash_logo,
            'app_logo_url' => $app_logo,
            'onboarding' => [
                [
                    'title' => $raw['onboarding_title_1'] ?? 'Easy Business Management',
                    'description' => $raw['onboarding_desc_1'] ?? 'Manage your sales, inventory, and accounts with ease.',
                ],
                [
                    'title' => $raw['onboarding_title_2'] ?? 'Real-time Reports',
                    'description' => $raw['onboarding_desc_2'] ?? 'Track your profits, losses, and cashflow in real-time.',
                ],
                [
                    'title' => $raw['onboarding_title_3'] ?? 'Fast POS Billing',
                    'description' => $raw['onboarding_desc_3'] ?? 'Quick invoice printing and barcode scanning at your fingertips.',
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => __('App settings fetched successfully.'),
            'data' => $data,
        ]);
    }
}
