<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory, ImageUrlTrait;

    protected $fillable = [
        'imageUrl',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    protected $imageFields = ['imageUrl'];
}