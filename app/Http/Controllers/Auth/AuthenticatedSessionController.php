<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Nwidart\Modules\Facades\Module;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Branch;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $remember = $request->filled('remember') ? 1 : 0;
        $redirect_url = url('/');
        $user = auth()->user();

        $fail = function ($message) use ($request) {
            Auth::logout();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect' => route('login')
                ], 406);
            }
            return redirect()->route('login')->with('error', $message);
        };

        if ($user->role == 'shop-owner' || $user->role == 'staff') {

            $module = Module::find('Business');

            if ($module) {
                if ($module->isEnabled()) {

                    $business = $user->business;
                    $branch = Branch::find($user->branch_id);

                    if ($business && !$business->status) {
                        return $fail('Your business is inactive. Please contact your administrator.');
                    }

                    if (multibranch_active() && branch_count()) {
                        if ($branch && !$branch->status && $user->branch_id && !$branch->is_main) {
                            return $fail('This branch is not active, Please contact with manager.');
                        }
                    } elseif (!multibranch_active()) {
                        if ($user->active_branch_id) {
                            $user->update([
                                'active_branch_id' => NULL
                            ]);
                        } elseif ($user->branch_id && !$branch->is_main) {
                            return $fail('Multibranch is not allowed in your current package, please upgrade your subscription plan.');
                        }
                    } elseif (!$branch && $user->branch_id) {
                        return $fail('Your current branch has been deleted, Please contact with manager.');
                    }

                    $redirect_url = route('business.dashboard.index');
                } else {
                    return $fail('Web addon is not active.');
                }
            } else {
                return $fail('Web addon is not installed.');
            }
        } else if ($user->role == 'affiliator') {

            $module = Module::find('AffiliateAddon');

            if ($module) {
                if ($module->isEnabled()) {

                    $redirect_url = route('business.dashboard.index');
                } else {
                    return $fail('Affiliate addon is not active.');
                }
            } else {
                return $fail('affiliate addon is not installed.');
            }
        } else {
            $redirect_url = route('admin.dashboard.index');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => __('Logged In Successfully'),
                'remember' => $remember,
                'redirect' => $redirect_url,
            ]);
        }

        return redirect()->intended($redirect_url)->with('success', __('Logged In Successfully'));
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
