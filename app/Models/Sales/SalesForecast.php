<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesForecast extends Model
{
    use HasFactory;

    protected $table = 'SalesForecast';
    protected $primaryKey = 'forecast_id';
    public $timestamps = false;
    
    protected $fillable = [
        'item_id',
        'customer_id',
        'forecast_period',
        'forecast_quantity',
        'actual_quantity',
        'variance'
    ];

    protected $casts = [
        'forecast_period' => 'date',
    ];

    /**
     * Get the item that the sales forecast belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get the customer that the sales forecast belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}