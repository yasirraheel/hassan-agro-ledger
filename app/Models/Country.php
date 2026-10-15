<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory, ImageUrlTrait;

    protected $fillable = [
        'name',
        'code',
        'image',
        'status',
    ];

    public function states()
    {
        return $this->hasMany(State::class);
    }

    protected $imageFields = ['image'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'integer',
    ];
}
