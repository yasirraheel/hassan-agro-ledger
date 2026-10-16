<?php

namespace App\Models;

use App\Traits\ImageUrlTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, ImageUrlTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'productName',
        'business_id',
        'unit_id',
        'brand_id',
        'vat_id',
        'vat_type',
        'vat_amount',
        'profit_percent',
        'category_id',
        'productCode',
        'productPicture',
        'productDealerPrice',
        'productPurchasePrice',
        'price_without_tax',
        'productSalePrice',
        'productWholeSalePrice',
        'productStock',
        'alert_qty',
        'expire_date',
        'meta',
        'productManufacturer',
        'model_id',
        'warehouse_id',
        'product_type',
        'rack_id',
        'shelf_id',
        'variation_ids',
        'has_serial',
        'warranty_guarantee_info',
        'hsn_code',
    ];

    public function saleDetails()
    {
        return $this->hasMany(SaleDetails::class, 'product_id', 'id');
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetails::class, 'product_id', 'id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function combo_products()
    {
        return $this->hasMany(ComboProduct::class, 'product_id', 'id')->with('stock');
    }

    public function product_model()
    {
        return $this->belongsTo(ProductModel::class, 'model_id', 'id');
    }

    public function vat()
    {
        return $this->belongsTo(Vat::class);
    }

    public function taxDisplay(bool $hideExclusive = false): string
    {
        if ($hideExclusive && $this->vat_type === 'exclusive') {
            return '';
        }

        $vat = $this->relationLoaded('vat') ? $this->vat : $this->vat()->first(['id', 'name', 'rate']);

        if (!$vat) {
            return '';
        }

        return trim($vat->name.' ('.($vat->rate ?? 0).'%)');
    }

    public function batch()
    {
        return $this->hasOne(Stock::class)
            ->where('productStock', '>', 0)
            ->latestOfMany();
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'model_id' => 'integer',
        'warehouse_id' => 'integer',
        'business_id' => 'integer',
        'unit_id' => 'integer',
        'brand_id' => 'integer',
        'vat_id' => 'integer',
        'category_id' => 'integer',
        'has_serial' => 'integer',
        'productDealerPrice' => 'double',
        'price_without_tax' => 'double',
        'productPurchasePrice' => 'double',
        'productSalePrice' => 'double',
        'productWholeSalePrice' => 'double',
        'productStock' => 'double',
        'vat_amount' => 'double',
        'profit_percent' => 'double',
        'alert_qty' => 'double',
        'stocks_sum_product_stock' => 'double',
        'meta' => 'json',
        'variation_ids' => 'json',
        'warranty_guarantee_info' => 'json',

        // Aggregated SUM fields
        'sale_details_sum_quantities' => 'double',
        'purchase_details_sum_quantities' => 'double',
    ];

    protected $imageFields = ['productPicture'];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            \App\Services\PlanLimitService::check('product_limit', self::class, $model->business_id);
        });
    }

    /**
     * Recalculates and syncs summary stock and prices from the stocks table to the products table.
     */
    public function syncStockAndPrices(): void
    {
        $stocks = $this->stocks()->withoutGlobalScopes()->get();
        if ($stocks->isNotEmpty()) {
            $totalStock = (double) $stocks->sum('productStock');
            $latestStock = $stocks->sortByDesc('id')->first();

            $updateData = [
                'productStock' => $totalStock,
            ];

            if ($latestStock) {
                if ($latestStock->productPurchasePrice > 0) {
                    $updateData['productPurchasePrice'] = $latestStock->productPurchasePrice;
                }
                if ($latestStock->productSalePrice > 0) {
                    $updateData['productSalePrice'] = $latestStock->productSalePrice;
                }
                if ($latestStock->productWholeSalePrice > 0) {
                    $updateData['productWholeSalePrice'] = $latestStock->productWholeSalePrice;
                }
                if ($latestStock->productDealerPrice > 0) {
                    $updateData['productDealerPrice'] = $latestStock->productDealerPrice;
                }
                if ($latestStock->profit_percent > 0) {
                    $updateData['profit_percent'] = $latestStock->profit_percent;
                }
            }

            $this->updateQuietly($updateData);
        }
    }

    public function getProductStockAttribute($value)
    {
        if (($value === null || (float)$value == 0) && $this->relationLoaded('stocks') && $this->stocks->isNotEmpty()) {
            return (double) $this->stocks->sum('productStock');
        }
        return (double) $value;
    }

    public function getProductPurchasePriceAttribute($value)
    {
        if (($value === null || (float)$value == 0) && $this->relationLoaded('stocks') && $this->stocks->isNotEmpty()) {
            return (double) ($this->stocks->sortByDesc('id')->first()?->productPurchasePrice ?? 0);
        }
        return (double) $value;
    }

    public function getProductSalePriceAttribute($value)
    {
        if (($value === null || (float)$value == 0) && $this->relationLoaded('stocks') && $this->stocks->isNotEmpty()) {
            return (double) ($this->stocks->sortByDesc('id')->first()?->productSalePrice ?? 0);
        }
        return (double) $value;
    }
}
