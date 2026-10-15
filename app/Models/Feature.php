<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory, ImageUrlTrait;

    protected $fillable = [
        'title',
        'bg_color',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    protected $imageFields = ['image'];

}
