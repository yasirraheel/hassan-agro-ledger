<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosAppInterface extends Model
{
    use HasFactory, ImageUrlTrait;

    protected $fillable = [
        'image',
        'status'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    protected $imageFields = ['image'];

}
