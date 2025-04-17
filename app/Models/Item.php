<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'maximum_stock'
    ];

    /**
     * Get the category that this item belongs to
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id', 'category_id');
    }

    /**
     * Get the unit of measure for this item
     */
    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id', 'uom_id');
    }

    /**
     * Get the batches for this item
     */
    public function batches()
    {
        return $this->hasMany(ItemBatch::class, 'item_id', 'item_id');
    }

    /**
     * Get the stock transactions for this item
     */
    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'item_id', 'item_id');
    }

    /**
     * Get the consignment stock for this item
     */
    public function consignmentStocks()
    {
        return $this->hasMany(ConsignmentStock::class, 'item_id', 'item_id');
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute()
    {
        if ($this->current_stock <= $this->minimum_stock) {
            return 'low';
        } elseif ($this->current_stock >= $this->maximum_stock) {
            return 'over';
        } else {
            return 'optimal';
        }
    }
}