<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustment_lines';
    protected $primaryKey = 'line_id';
    protected $fillable = [
        'adjustment_id', 
        'item_id', 
        'warehouse_id', 
        //'location_id', 
        'book_quantity', 
        'adjusted_quantity', 
        'variance'
    ];

    /**
     * Get the stock adjustment that owns this line
     */
    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id', 'adjustment_id');
    }

    /**
     * Get the item for this adjustment line
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the warehouse for this adjustment line
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    /**
     * Get the location for this adjustment line
     */
    // public function location()
    // {
    //     return $this->belongsTo(WarehouseLocation::class, 'location_id', 'location_id');
    // }

    /**
     * Calculate variance percentage
     */
    public function getVariancePercentageAttribute()
    {
        if ($this->book_quantity == 0) {
            return null; // Avoid division by zero
        }
        
        return ($this->variance / $this->book_quantity) * 100;
    }
}