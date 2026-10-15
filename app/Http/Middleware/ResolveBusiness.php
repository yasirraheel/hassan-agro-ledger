<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Business;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveBusiness
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!moduleCheck('CustomDomainAddon') && !moduleCheck('RestaurantWebAddon')) {
            app()->instance('currentBusiness', null);
            view()->share('currentBusiness', null);
            return $next($request);
        }

        $host = $request->getHost();
        $slug = $request->route('restaurant_slug');

        $business = null;
        if (moduleCheck('CustomDomainAddon')) {
            $domain = \Modules\CustomDomainAddon\App\Models\Domain::with('business')
                        ->where('domain', $host)
                        ->where('is_verified', 1)
                        ->where('status', 1)
                        ->first();

            if ($domain) {
                $business = $domain->business;
            }
        }

        if (!$business && $slug) {
            $business = Business::where('slug', $slug)->first();
        }

        if (!$business) {
            abort(404, 'Restaurant not found.');
        }

        app()->instance('currentBusiness', $business);
        view()->share('currentBusiness', $business);

        return $next($request);
    }
}
