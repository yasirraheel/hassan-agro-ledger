<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'duration',
        'offerPrice',
        'subscriptionName',
        'subscriptionPrice',
        'visibility',
        'features',
        'affiliate_commission',
        'available_modules',
        'allow_multibranch',
        'addon_domain_limit',
        'subdomain_limit',
        'user_limit',
        'product_limit',
        'customer_limit',
        'supplier_limit',
        'warehouse_limit',
        'branch_limit',
    ];

    public function planSubscribes()
    {
        return $this->hasMany(PlanSubscribe::class, 'plan_id');
    }


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'features' => 'json',
        'duration' => 'integer',
        'offerPrice' => 'double',
        'status' => 'integer',
        'visibility' => 'json',
        'available_modules' => 'json',
        'subscriptionPrice' => 'double',
        'allow_multibranch' => 'integer',
        'addon_domain_limit' => 'integer',
        'subdomain_limit' => 'integer',
        'user_limit' => 'integer',
        'product_limit' => 'integer',
        'customer_limit' => 'integer',
        'supplier_limit' => 'integer',
        'warehouse_limit' => 'integer',
        'branch_limit' => 'integer',
    ];
}
