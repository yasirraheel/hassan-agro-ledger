<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'status',
        'sub_vat',
        'business_id',
        'manage_state',
    ];

    public function stateVats()
    {
        return $this->hasMany(VatStateItem::class, 'parent_vat_id');
    }

    public function innerStateVats()
    {
        return $this->hasMany(VatStateItem::class, 'parent_vat_id')->where('type', 'inner');
    }

    public function outerStateVats()
    {
        return $this->hasMany(VatStateItem::class, 'parent_vat_id')->where('type', 'outer');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'rate' => 'double',
        'sub_vat' => 'json',
        'status' => 'boolean',
        'business_id' => 'integer',
        'manage_state' => 'integer',
    ];
}
