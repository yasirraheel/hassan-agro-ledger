<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory, ImageUrlTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'brandName',
        'description',
        'business_id',
        'icon',
        'status',
    ];

    protected $casts = [
        'business_id' => 'integer',
        'status' => 'integer'
    ];

    protected $imageFields = ['icon'];
}
