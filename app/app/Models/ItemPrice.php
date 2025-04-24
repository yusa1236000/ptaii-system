<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPrice extends Model
{
    use HasFactory;

    protected $table = 'item_prices';
    protected $primaryKey = 'price_id';
    
    protected $fillable = [
        'item_id',
        'price_type', // 'purchase' or 'sale'
        'price',
        'currency',
        'min_quantity', // For quantity-based pricing
        'start_date',
        'end_date',
        'customer_id', // For customer-specific pricing, nullable
        'vendor_id',   // For vendor-specific pricing, nullable
        'is_active'
    ];

    protected $casts = [
        'price' => 'float',
        'min_quantity' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the item that owns the price.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    
    /**
     * Get the customer if this is a customer-specific price.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    
    /**
     * Get the vendor if this is a vendor-specific price.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
    
    /**
     * Scope a query to only include active prices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     })
                     ->where(function($q) {
                         $q->whereNull('start_date')
                           ->orWhere('start_date', '<=', now());
                     });
    }
    
    /**
     * Scope a query to only include purchase prices.
     */
    public function scopePurchase($query)
    {
        return $query->where('price_type', 'purchase');
    }
    
    /**
     * Scope a query to only include sale prices.
     */
    public function scopeSale($query)
    {
        return $query->where('price_type', 'sale');
    }
}