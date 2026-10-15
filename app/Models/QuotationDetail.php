<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'stock_id',
        'batch_no',
        'unit_name',
        'price',
        'discount',
        'quantities',
        'sub_total',
        'thickness',
        'length',
        'height',
        'width',
        'serial_numbers',
        'meta',
    ];

    protected $casts = [
        'quotation_id' => 'integer',
        'product_id' => 'integer',
        'stock_id' => 'integer',
        'price' => 'double',
        'discount' => 'double',
        'quantities' => 'double',
        'sub_total' => 'double',
        'thickness' => 'double',
        'length' => 'double',
        'height' => 'double',
        'width' => 'double',
        'serial_numbers' => 'json',
        'meta' => 'json',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
