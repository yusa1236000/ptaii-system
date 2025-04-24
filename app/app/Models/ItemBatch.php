<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemBatch extends Model
{
    use HasFactory;

    protected $table = 'item_batches';
    protected $primaryKey = 'batch_id';
    protected $fillable = [
        'item_id', 
        'batch_number', 
        'expiry_date', 
        'manufacturing_date', 
        'lot_number'
    ];

    protected $dates = [
        'expiry_date',
        'manufacturing_date',
    ];

    /**
     * Get the item that owns this batch
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the stock transactions for this batch
     */
    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'batch_id', 'batch_id');
    }

    /**
     * Check if batch is expired
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }
        if ($this->isExpired()) {
            return 0;
        }
        return now()->diffInDays($this->expiry_date);
    }
}