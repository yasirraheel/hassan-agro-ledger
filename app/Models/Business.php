<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AffiliateAddon\App\Models\Affiliate;

class Business extends Model
{
    use HasFactory, ImageUrlTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'plan_subscribe_id',
        'business_category_id',
        'companyName',
        'address',
        'phoneNumber',
        'pictureUrl',
        'will_expire',
        'subscriptionDate',
        'remainingShopBalance',
        'shopOpeningBalance',
        'vat_name',
        'vat_no',
        'affiliator_id',
        'email',
        'status',
        'meta',
        'country_id',
        'state_id',
    ];

    public function enrolled_plan()
    {
        return $this->belongsTo(PlanSubscribe::class, 'plan_subscribe_id');
    }

    public function category()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function user()
    {
        return $this->hasMany(User::class);
    }

    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'affiliator_id');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'country_id' => 'integer',
        'state_id' => 'integer',
        'plan_subscribe_id' => 'integer',
        'business_category_id' => 'integer',
        'remainingShopBalance' => 'double',
        'shopOpeningBalance' => 'double',
        'status' => 'integer',
        'meta' => 'json',
    ];

    protected $imageFields = ['pictureUrl'];

}
