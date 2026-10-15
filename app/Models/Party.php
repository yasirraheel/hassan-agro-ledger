<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    use HasFactory, ImageUrlTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'due',
        'image',
        'status',
        'address',
        'branch_id',
        'business_id',
        'credit_limit',
        'loyalty_points',
        'wallet',
        'opening_balance',
        'opening_balance_type',
        'billing_address',
        'shipping_address',
        'meta',
        'tax_no',
        'country_id',
        'state_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->branch_id = $model->branch_id ?? optional(auth()->user())->branch_id ?? optional(auth()->user())->active_branch_id ?? null;

            if (in_array($model->type, ['Retailer', 'Dealer', 'Wholesaler'])) {
                \App\Services\PlanLimitService::check('customer_limit', 'customer', $model->business_id);
            } elseif ($model->type === 'Supplier') {
                \App\Services\PlanLimitService::check('supplier_limit', 'supplier', $model->business_id);
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function canBeDeleted(): bool
    {
        // Party cannot be deleted if it has sales or purchases
        if ($this->sales()->exists() || $this->purchases()->exists()) {
            return false;
        }

        // Party cannot be deleted if due != opening_balance or wallet != 0
        if ($this->due != $this->opening_balance || $this->wallet != 0) {
            return false;
        }

        return true;
    }

    public function sales_dues() : HasMany
    {
        return $this->hasMany(Sale::class)->where('dueAmount', '>', 0);
    }

    public function purchases_dues() : HasMany
    {
        return $this->hasMany(Purchase::class)->where('dueAmount', '>', 0);
    }

    public function dueCollect()
    {
        return $this->hasOne(DueCollect::class);
    }

    public function dueForBranch($branchId = null)
    {
        $branchId = $branchId ?? auth()->user()->active_branch?->id;

        if (!$branchId) return $this->due;

        $dues = $this->type === 'Supplier'
            ? $this->purchases()->where('branch_id', $branchId)->where('dueAmount', '>', 0)
            : $this->sales()->where('branch_id', $branchId)->where('dueAmount', '>', 0);

        $party_due = $dues->sum('dueAmount');

        if ($this->branch_id === $branchId) {
            $openingBalanceDue = $this->opening_balance_type === 'due' ? ($this->opening_balance ?? 0) : 0;
            return $openingBalanceDue + $party_due;
        }

        return $party_due;
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'due' => 'double',
        'wallet' => 'double',
        'business_id' => 'integer',
        'country_id' => 'integer',
        'state_id' => 'integer',
        'status' => 'integer',
        'meta' => 'json',
        'credit_limit' => 'double',
        'loyalty_points' => 'double',
        'opening_balance' => 'double',
        'billing_address' => 'json',
        'shipping_address' => 'json',

        // Aggregated fields
        'sales_count' => 'integer',
        'purchases_count' => 'integer',
        'total_sale_amount' => 'double',
        'total_sale_paid' => 'double',
        'total_sale_profit' => 'double',
        'total_sale_loss' => 'double',
        'total_purchase_amount' => 'double',
        'total_purchase_paid' => 'double',
    ];

    protected $imageFields = ['image'];

}
