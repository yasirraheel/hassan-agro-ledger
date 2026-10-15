<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory, ImageUrlTrait;

    protected $fillable = [
        'name',
        'icon',
        'status',

        'code',
        'order',
        'is_default',
    ];

    protected $casts = [
        'status' => 'integer',
        'order' => 'integer',
        'is_default' => 'boolean',
    ];

    protected $imageFields = ['icon'];
}
