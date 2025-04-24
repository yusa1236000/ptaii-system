<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

    protected $table = 'Delivery';
    protected $primaryKey = 'delivery_id';
    public $timestamps = false;

    protected $fillable = [
        'delivery_number',
        'delivery_date',
        'so_id',
        'customer_id',
        'status',
        'shipping_method',
        'tracking_number'
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    /**
     * Get the sales order that owns the delivery.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    /**
     * Get the customer that owns the delivery.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the delivery lines for the delivery.
     */
    public function deliveryLines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class, 'delivery_id');
    }
}