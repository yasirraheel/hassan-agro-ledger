<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'party_id',
        'user_id',
        'branch_id',
        'warehouse_id',
        'category_id',
        'brand_id',
        'vat_id',
        'sale_id',
        'reference',
        'quotation_no',
        'quotation_date',
        'expire_date',
        'is_converted',
        'converted_at',
        'discount_type',
        'discountAmount',
        'discount_percent',
        'vat_amount',
        'vat_percent',
        'shipping_charge',
        'totalAmount',
        'billing_address',
        'shipping_address',
        'terms',
        'note',
        'meta',
    ];

    protected $casts = [
        'party_id' => 'integer',
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'vat_id' => 'integer',
        'sale_id' => 'integer',
        'quotation_date' => 'datetime',
        'expire_date' => 'datetime',
        'converted_at' => 'datetime',
        'is_converted' => 'boolean',
        'discountAmount' => 'double',
        'discount_percent' => 'double',
        'vat_amount' => 'double',
        'vat_percent' => 'double',
        'shipping_charge' => 'double',
        'totalAmount' => 'double',
        'billing_address' => 'json',
        'shipping_address' => 'json',
        'meta' => 'json',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function vat(): BelongsTo
    {
        return $this->belongsTo(Vat::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(QuotationDetail::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->branch_id = $model->branch_id ?? auth()->user()->branch_id ?? auth()->user()->active_branch_id ?? null;
        });
    }

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);

        if (auth()->check() && auth()->user()->accessToMultiBranch()) {
            static::addGlobalScope('withBranch', function ($builder) {
                $builder->with('branch:id,name');
            });
        }
    }
}
