<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppSettingsController extends Controller
{
    use HasUploader;

    public function index()
    {
        $app_settings = Option::where('key', 'app_settings')->first();
        $settings = $app_settings?->value ?? [
            'app_name' => 'Business Manager',
            'powered_by' => 'Business Manager',
            'company_website' => 'https://hassanagro.com',
            'splash_bg_color' => '#C52127',
            'primary_color' => '#C52127',
            'app_version' => '1.0.0',
            'support_email' => '',
            'support_phone' => '',
            'splash_logo' => '',
            'app_logo' => '',
            'onboarding_title_1' => 'Easy Business Management',
            'onboarding_desc_1' => 'Manage your sales, inventory, and accounts with ease.',
            'onboarding_title_2' => 'Real-time Reports',
            'onboarding_desc_2' => 'Track your profits, losses, and cashflow in real-time.',
            'onboarding_title_3' => 'Fast POS Billing',
            'onboarding_desc_3' => 'Quick invoice printing and barcode scanning at your fingertips.',
        ];

        return view('admin.settings.app-settings', compact('settings', 'app_settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:100',
            'powered_by' => 'nullable|string|max:100',
            'company_website' => 'nullable|url',
            'splash_bg_color' => 'nullable|string|max:20',
            'primary_color' => 'nullable|string|max:20',
            'app_version' => 'nullable|string|max:20',
            'support_email' => 'nullable|email|max:100',
            'support_phone' => 'nullable|string|max:50',
            'splash_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:4096',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:4096',
            'onboarding_title_1' => 'nullable|string|max:150',
            'onboarding_desc_1' => 'nullable|string|max:300',
            'onboarding_title_2' => 'nullable|string|max:150',
            'onboarding_desc_2' => 'nullable|string|max:300',
            'onboarding_title_3' => 'nullable|string|max:150',
            'onboarding_desc_3' => 'nullable|string|max:300',
        ]);

        $option = Option::firstOrCreate(['key' => 'app_settings'], ['value' => []]);
        $current = $option->value ?? [];

        $data = [
            'app_name' => $request->app_name ?? 'Business Manager',
            'powered_by' => $request->powered_by ?? 'Business Manager',
            'company_website' => $request->company_website,
            'splash_bg_color' => $request->splash_bg_color ?? '#C52127',
            'primary_color' => $request->primary_color ?? '#C52127',
            'app_version' => $request->app_version ?? '1.0.0',
            'support_email' => $request->support_email,
            'support_phone' => $request->support_phone,
            'onboarding_title_1' => $request->onboarding_title_1,
            'onboarding_desc_1' => $request->onboarding_desc_1,
            'onboarding_title_2' => $request->onboarding_title_2,
            'onboarding_desc_2' => $request->onboarding_desc_2,
            'onboarding_title_3' => $request->onboarding_title_3,
            'onboarding_desc_3' => $request->onboarding_desc_3,
            'splash_logo' => $request->hasFile('splash_logo')
                ? $this->upload($request, 'splash_logo', $current['splash_logo'] ?? null)
                : ($current['splash_logo'] ?? null),
            'app_logo' => $request->hasFile('app_logo')
                ? $this->upload($request, 'app_logo', $current['app_logo'] ?? null)
                : ($current['app_logo'] ?? null),
        ];

        $option->update(['value' => $data]);
        Cache::forget('app_settings');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => __('Mobile App Settings updated successfully'),
                'redirect' => route('admin.app-settings.index'),
            ]);
        }

        return redirect()->back()->with('success', __('Mobile App Settings updated successfully'));
    }
}
