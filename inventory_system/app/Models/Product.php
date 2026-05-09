<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'unit',
        'barcode',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'price'              => 'decimal:2',
        'cost_price'         => 'decimal:2',
        'stock_quantity'     => 'integer',
        'low_stock_threshold'=> 'integer',
        'is_active'          => 'boolean',
    ];

    protected $appends = [
        'stock_status',
        'profit_margin',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot(['supply_price', 'lead_time_days', 'is_preferred'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', 0);
    }

    public function scopePriceRange($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function scopeInCategory($query, int|array $categoryId)
    {
        return $query->whereIn('category_id', (array) $categoryId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('sku', 'LIKE', "%{$term}%")
              ->orWhere('barcode', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%");
        });
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity === 0) {
            return 'out_of_stock';
        }

        if ($this->stock_quantity <= $this->low_stock_threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function getProfitMarginAttribute(): ?float
    {
        if (! $this->cost_price || $this->cost_price == 0) {
            return null;
        }

        return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    public function setSkuAttribute(?string $value): void
    {
        $this->attributes['sku'] = $value ?? strtoupper(Str::random(8));
    }
}