<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VatStateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_vat_id',
        'child_vat_id',
        'type'
    ];

    public function parentVat()
    {
        return $this->belongsTo(Vat::class, 'parent_vat_id');
    }

    public function childVat()
    {
        return $this->belongsTo(Vat::class, 'child_vat_id');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'parent_vat_id' => 'integer',
        'child_vat_id' => 'integer',
    ];
}
