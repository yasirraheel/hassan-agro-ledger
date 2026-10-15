<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory, ImageUrlTrait;

    protected $fillable = [
        'text',
        'star',
        'client_name',
        'client_image',
        'work_at'
    ];

    protected $casts = [
        'star' => 'integer',
    ];

    protected $imageFields = ['client_image'];

}
