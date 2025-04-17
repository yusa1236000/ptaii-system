<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;

    protected $table = 'stock_transactions';
    protected $primaryKey = 'transaction_id';
    protected $fillable = [
        'item_id', 
        'warehouse_id', 
        'location_id', 
        'transaction_type', 
        'quantity', 
        'transaction_date', 
        'reference_document',
        'reference_number',
        'batch_id'
    ];

    protected $dates = [
        'transaction_date',
    ];

    const TYPE_RECEIVE = 'receive';
    const TYPE_ISSUE = 'issue';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RETURN = 'return';
    
    /**
     * Get the item for this transaction
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the warehouse for this transaction
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    /**
     * Get the location for this transaction
     */
    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id', 'location_id');
    }

    /**
     * Get the batch for this transaction
     */
    public function batch()
    {
        return $this->belongsTo(ItemBatch::class, 'batch_id', 'batch_id');
    }

    /**
     * Scope for incoming transactions (positive quantity)
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('transaction_type', [
            self::TYPE_RECEIVE, 
            self::TYPE_RETURN, 
            self::TYPE_ADJUSTMENT
        ])->where('quantity', '>', 0);
    }

    /**
     * Scope for outgoing transactions (negative quantity)
     */
    public function scopeOutgoing($query)
    {
        return $query->whereIn('transaction_type', [
            self::TYPE_ISSUE, 
            self::TYPE_TRANSFER, 
            self::TYPE_ADJUSTMENT
        ])->where('quantity', '<', 0);
    }
}