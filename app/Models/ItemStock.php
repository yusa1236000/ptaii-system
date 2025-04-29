<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemStock extends Model
{
    use HasFactory;

    protected $table = 'ItemStock';
    protected $primaryKey = 'stock_id';
    
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity'
    ];

    protected $casts = [
        'quantity' => 'float',
        'reserved_quantity' => 'float',
    ];

    /**
     * Get the item that owns the stock.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get the warehouse that owns the stock.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get available quantity (total - reserved)
     */
    public function getAvailableQuantityAttribute()
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Check if there's enough available stock
     *
     * @param float $requestedQuantity
     * @return bool
     */
    public function hasEnoughAvailable($requestedQuantity)
    {
        return $this->available_quantity >= $requestedQuantity;
    }

    /**
     * Static method to get or create stock record
     *
     * @param int $itemId
     * @param int $warehouseId
     * @return ItemStock
     */
    public static function getOrCreate($itemId, $warehouseId)
    {
        $stock = self::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->first();
            
        if (!$stock) {
            $stock = self::create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'quantity' => 0,
                'reserved_quantity' => 0
            ]);
        }
        
        return $stock;
    }

    /**
     * Increase stock quantity
     *
     * @param float $quantity
     * @return void
     */
    public function increaseStock($quantity)
    {
        $this->quantity += $quantity;
        $this->save();
        
        // Update item's total stock
        $this->item->increment('current_stock', $quantity);
    }

    /**
     * Decrease stock quantity
     *
     * @param float $quantity
     * @return bool
     */
    public function decreaseStock($quantity)
    {
        if ($this->quantity < $quantity) {
            return false;
        }
        
        $this->quantity -= $quantity;
        $this->save();
        
        // Update item's total stock
        $this->item->decrement('current_stock', $quantity);
        
        return true;
    }

    /**
     * Reserve stock
     *
     * @param float $quantity
     * @return bool
     */
    public function reserve($quantity)
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }
        
        $this->reserved_quantity += $quantity;
        $this->save();
        
        return true;
    }

    /**
     * Release reserved stock
     *
     * @param float $quantity
     * @return bool
     */
    public function releaseReservation($quantity)
    {
        if ($this->reserved_quantity < $quantity) {
            return false;
        }
        
        $this->reserved_quantity -= $quantity;
        $this->save();
        
        return true;
    }
}