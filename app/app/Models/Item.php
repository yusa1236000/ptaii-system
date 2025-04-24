<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UnitOfMeasure;
use App\Models\ItemCategory;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $primaryKey = 'item_id';
    
    protected $fillable = [
        'item_code',
        'name',
        'description',
        'category_id',
        'uom_id',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'is_purchasable',   // New field
        'is_sellable',      // New field
        'cost_price',       // New field - default cost price
        'sale_price',       // New field - default sale price
    ];

    protected $casts = [
        'current_stock' => 'float',
        'minimum_stock' => 'float',
        'maximum_stock' => 'float',
        'is_purchasable' => 'boolean',
        'is_sellable' => 'boolean',
        'cost_price' => 'float',
        'sale_price' => 'float'
    ];

    // Existing relationships and methods...

    /**
     * Get all prices for this item.
     */
    public function prices()
    {
        return $this->hasMany(ItemPrice::class, 'item_id');
    }
    
    /**
     * Get all purchase prices for this item.
     */
    public function purchasePrices()
    {
        return $this->hasMany(ItemPrice::class, 'item_id')->purchase();
    }
    
    /**
     * Get all sale prices for this item.
     */
    public function salePrices()
    {
        return $this->hasMany(ItemPrice::class, 'item_id')->sale();
    }
    
    /**
     * Get default purchase price for this item.
     */
    public function getDefaultPurchasePriceAttribute()
    {
        return $this->cost_price;
    }
    
    /**
     * Get default sale price for this item.
     */
    public function getDefaultSalePriceAttribute()
    {
        return $this->sale_price;
    }
    
    /**
     * Get the best purchase price for a specific vendor and quantity.
     */
    public function getBestPurchasePrice($vendorId = null, $quantity = 1)
    {
        // First try to find a vendor-specific price for the given quantity
        if ($vendorId) {
            $vendorPrice = $this->purchasePrices()
                ->active()
                ->where('vendor_id', $vendorId)
                ->where('min_quantity', '<=', $quantity)
                ->orderBy('price', 'asc')
                ->orderBy('min_quantity', 'desc')
                ->first();
                
            if ($vendorPrice) {
                return $vendorPrice->price;
            }
        }
        
        // Next try to find a general purchase price for the given quantity
        $generalPrice = $this->purchasePrices()
            ->active()
            ->whereNull('vendor_id')
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('price', 'asc')
            ->orderBy('min_quantity', 'desc')
            ->first();
            
        if ($generalPrice) {
            return $generalPrice->price;
        }
        
        // If no price found, return the default cost price
        return $this->cost_price;
    }
    
    /**
     * Get the best sale price for a specific customer and quantity.
     */
    public function getBestSalePrice($customerId = null, $quantity = 1)
    {
        // First try to find a customer-specific price for the given quantity
        if ($customerId) {
            $customerPrice = $this->salePrices()
                ->active()
                ->where('customer_id', $customerId)
                ->where('min_quantity', '<=', $quantity)
                ->orderBy('price', 'asc')
                ->orderBy('min_quantity', 'desc')
                ->first();
                
            if ($customerPrice) {
                return $customerPrice->price;
            }
        }
        
        // Next try to find a general sale price for the given quantity
        $generalPrice = $this->salePrices()
            ->active()
            ->whereNull('customer_id')
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('price', 'asc')
            ->orderBy('min_quantity', 'desc')
            ->first();
            
        if ($generalPrice) {
            return $generalPrice->price;
        }
        
        // If no price found, return the default sale price
        return $this->sale_price;
    }
    /**
     * Get the category that owns the item.
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }
    /**
     * Get the unit of measure that owns the item.
     */
    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }
}