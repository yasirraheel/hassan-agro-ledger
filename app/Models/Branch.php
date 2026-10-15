<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HrmAddon\App\Models\Employee;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'is_main',
        'address',
        'description',
        'status',
        'branchOpeningBalance',
        'branchRemainingBalance',
        'country_id',
        'state_id'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->business_id = $model->business_id ?? optional(auth()->user())->business_id;

            if (!$model->business_id) {
                return;
            }

            if (!multibranch_active()) {
                throw ValidationException::withMessages([
                    'branch_limit' => __('Multi-branch is not allowed in your plan.'),
                ]);
            }

            \App\Services\PlanLimitService::check('branch_limit', self::class, $model->business_id);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function employees(): ?HasMany
    {
        return moduleCheck('MultiBranchAddon')
            ? $this->hasMany(Employee::class, 'branch_id')
            : null;
    }

    public function expiredStocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'branch_id')->whereDate('expire_date', '<', today())->where('productStock', '>', 0);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'branch_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'branch_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    protected $casts = [
        'business_id' => 'integer',
        'country_id' => 'integer',
        'state_id' => 'integer',
        'is_main' => 'integer',
        'status' => 'integer',
        'branchOpeningBalance' => 'double',
        'branchRemainingBalance' => 'double',
    ];
}
