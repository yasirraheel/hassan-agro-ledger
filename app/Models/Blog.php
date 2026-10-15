<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory, ImageUrlTrait;

    protected $fillable = ['user_id', 'title', 'slug', 'image', 'status', 'descriptions', 'tags', 'meta'];

    protected $casts = [
        'user_id' => 'integer',
        'tags' => 'json',
        'meta' => 'json',
        'status' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($blog) {
            $blog->slug = Str::slug($blog->title);
        });
    }

    protected $imageFields = ['image'];
}
