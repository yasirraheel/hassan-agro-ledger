<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VatStateItem;
use App\Models\Vat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcnooVatController extends Controller
{
    public function index()
    {
        $vats = Vat::with(['innerStateVats', 'outerStateVats'])->where('business_id', auth()->user()->business_id)
                    ->when(request('type') == 'single', function ($query) {
                        $query->whereNull('sub_vat');
                    })
                    ->when(request('type') == 'group', function ($query) {
                        $query->whereNotNull('sub_vat');
                    })
                    ->when(request('status'), function ($query) {
                        $query->where('status', request('status') == 'active' ? 1 : 0);
                    })
                    ->latest()
                    ->get();

        return response()->json([
            'message' => 'Data fetched successfully.',
            'data' => $vats,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vat_ids' => 'array',
            'rate' => 'nullable|numeric',
            'manage_state' => 'nullable|integer|in:0,1'
        ]);

        $businessId = auth()->user()->business_id;

        // SINGLE VAT
        if ($request->filled('rate') && empty($request->vat_ids) && !$request->manage_state) {

            $vat = Vat::create([
                'name' => $request->name,
                'rate' => $request->rate,
                'business_id' => $businessId,
                'manage_state' => 0,
            ]);

        }
        // GROUP VAT (OLD SYSTEM)
        elseif ($request->vat_ids && !$request->manage_state) {

            $vats = Vat::whereIn('id', $request->vat_ids)
                        ->where('business_id', $businessId)
                        ->select('id', 'name', 'rate')
                        ->get();

            $tax_rate = 0;
            $sub_vats = [];

            foreach ($vats as $vatItem) {
                $sub_vats[] = [
                    'id' => $vatItem->id,
                    'name' => $vatItem->name,
                    'rate' => $vatItem->rate,
                ];
                $tax_rate += $vatItem->rate;
            }

            $vat = Vat::create([
                'name' => $request->name,
                'rate' => $tax_rate,
                'sub_vat' => $sub_vats,
                'business_id' => $businessId,
                'manage_state' => 0,
            ]);

        }
        // STATE WISE VAT (NEW SYSTEM)
        elseif ($request->manage_state == 1) {

            $innerIds = $request->inner_vat_ids ?? [];
            $outerIds = $request->outer_vat_ids ?? [];

            $innerRate = Vat::whereIn('id', $innerIds)
                            ->where('business_id', $businessId)
                            ->sum('rate');

            $outerRate = Vat::whereIn('id', $outerIds)
                            ->where('business_id', $businessId)
                            ->sum('rate');

            $vat = Vat::create([
                'name' => $request->name,
                'rate' => $innerRate ?: $outerRate,
                'business_id' => $businessId,
                'manage_state' => 1,
            ]);

            // Save pivot inner
            foreach ($innerIds as $id) {
                VatStateItem::create([
                    'parent_vat_id' => $vat->id,
                    'child_vat_id'  => $id,
                    'type'          => 'inner'
                ]);
            }

            // Save pivot outer
            foreach ($outerIds as $id) {
                VatStateItem::create([
                    'parent_vat_id' => $vat->id,
                    'child_vat_id'  => $id,
                    'type'          => 'outer'
                ]);
            }

        }
        else {
            return response()->json([
                'message' => 'Invalid data format.'
            ], 406);
        }

        return response()->json([
            'message' => 'Vat created successfully',
            'data' => $vat,
        ]);
    }

    public function update(Request $request, Vat $vat)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vat_ids' => 'array',
            'rate' => 'nullable|numeric',
            'manage_state' => 'nullable|integer|in:0,1',
        ]);

        DB::beginTransaction();
        try {
            $businessId = auth()->user()->business_id;

            // Delete old state-wise VAT pivots
            VatStateItem::where('parent_vat_id', $vat->id)->delete();

            // SINGLE VAT
            if ($request->filled('rate') && empty($request->vat_ids) && !$request->manage_state) {

                $vat->update([
                    'name' => $request->name,
                    'rate' => $request->rate,
                    'status' => $request->status ?? $vat->status,
                    'manage_state' => 0,
                ]);

                // Update group VATs containing this single VAT
                $vatGroupExist = Vat::where('sub_vat', 'LIKE', '%"id":' . $vat->id . '%')->get();
                foreach ($vatGroupExist as $group) {
                    $subVats = collect($group->sub_vat)->map(function ($subVat) use ($vat) {
                        if ($subVat['id'] == $vat->id) {
                            $subVat['rate'] = $vat->rate;
                            $subVat['name'] = $vat->name;
                        }
                        return $subVat;
                    });

                    $group->update([
                        'rate' => $subVats->sum('rate'),
                        'sub_vat' => $subVats->toArray(),
                    ]);
                }
            }
            // GROUP VAT (OLD SYSTEM)
            elseif ($request->vat_ids && !$request->manage_state) {

                $vats = Vat::whereIn('id', $request->vat_ids)
                            ->where('business_id', $businessId)
                            ->select('id', 'name', 'rate')
                            ->get();

                $tax_rate = 0;
                $sub_vats = [];
                foreach ($vats as $vatItem) {
                    $sub_vats[] = [
                        'id' => $vatItem->id,
                        'name' => $vatItem->name,
                        'rate' => $vatItem->rate,
                    ];
                    $tax_rate += $vatItem->rate;
                }

                $vat->update([
                    'name' => $request->name,
                    'rate' => $tax_rate,
                    'sub_vat' => $sub_vats,
                    'status' => $request->status ?? $vat->status,
                    'manage_state' => 0,
                ]);
            }
            // STATE-WISE VAT (NEW SYSTEM)
            elseif ($request->manage_state == 1) {

                $innerIds = $request->inner_vat_ids ?? [];
                $outerIds = $request->outer_vat_ids ?? [];

                $innerRate = Vat::whereIn('id', $innerIds)
                                ->where('business_id', $businessId)
                                ->sum('rate');

                $outerRate = Vat::whereIn('id', $outerIds)
                                ->where('business_id', $businessId)
                                ->sum('rate');

                $vat->update([
                    'name' => $request->name,
                    'rate' => $innerRate ?: $outerRate,
                    'status' => $request->status ?? $vat->status,
                    'manage_state' => 1,
                    'sub_vat' => null,
                ]);

                // Insert inner VAT pivot
                foreach ($innerIds as $id) {
                    VatStateItem::create([
                        'parent_vat_id' => $vat->id,
                        'child_vat_id' => $id,
                        'type' => 'inner',
                    ]);
                }

                // Insert outer VAT pivot
                foreach ($outerIds as $id) {
                    VatStateItem::create([
                        'parent_vat_id' => $vat->id,
                        'child_vat_id' => $id,
                        'type' => 'outer',
                    ]);
                }
            }
            else {
                DB::rollBack();
                return response()->json([
                    'message' => 'Invalid data format.',
                ], 406);
            }

            DB::commit();
            return response()->json([
                'message' => 'Vat updated successfully',
                'data' => $vat,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy(Vat $vat)
    {
        // When sub_vat is null, check if the VAT exists in any other VAT's sub_vat
        if (is_null($vat->sub_vat) && Vat::where('sub_vat', 'LIKE', '%"id":' . $vat->id . '%')->exists()) {
            return response()->json([
                'message' => 'Cannot delete. This VAT is part of a VAT group.',
            ], 404);
        }

        $vat->delete();

        return response()->json([
            'message' => 'Data deleted successfully',
        ]);
    }

}
