<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransferProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'stock_id',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'serial_numbers'
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    protected $casts = [
        'transfer_id' => 'integer',
        'product_id' => 'integer',
        'stock_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'double',
        'discount' => 'double',
        'tax' => 'double',
        'serial_numbers' => 'json',
    ];

}
