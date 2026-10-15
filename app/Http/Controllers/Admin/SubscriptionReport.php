<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\PlanSubscribe;
use App\Http\Controllers\Controller;
use App\Traits\DateFilterTrait;

class SubscriptionReport extends Controller
{
    use DateFilterTrait;

    public function index(Request $request)
    {
        $subscribeQuery = PlanSubscribe::with(['plan:id,subscriptionName,offerPrice,subscriptionPrice', 'business:id,companyName,business_category_id,pictureUrl', 'business.category:id,name', 'gateway:id,name']);

        // Date Filter
        $duration = $request->custom_days ?: 'today';
        $this->applyDateFilter($subscribeQuery, $duration, 'created_at', $request->from_date, $request->to_date);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $subscribeQuery->where(function ($query) use ($search) {
                $query->where('duration', 'like', '%' . $search . '%')
                    ->orWhereHas('plan', function ($q) use ($search) {
                        $q->where('subscriptionName', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('gateway', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('business', function ($q) use ($search) {
                        $q->where('companyName', 'like', '%' . $search . '%')
                            ->orWhereHas('category', function ($q) use ($search) {
                                $q->where('name', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $perPage = $request->input('per_page', 20);
        $subscribers = $subscribeQuery->latest()->paginate($perPage)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.subscribers.datas', compact('subscribers'))->render()
            ]);
        }

        return view('admin.subscribers.index', compact('subscribers'));
    }

}
