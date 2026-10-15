<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;

class VatTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'vat_id',
        'branch_id',
        'vatable_id',
        'vatable_type',
        'vat_rate',
        'vat_amount',
    ];

    public function vat()
    {
        return $this->belongsTo(Vat::class);
    }

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'business_id' => 'integer',
        'vat_id' => 'integer',
        'branch_id' => 'integer',
        'vatable_id' => 'integer',
        'vat_rate' => 'double',
        'vat_amount' => 'double',
    ];
}
