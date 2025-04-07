<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Routing extends Model
{
    use HasFactory;

    protected $table = 'Routing';
    protected $primaryKey = 'routing_id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'routing_code',
        'revision',
        'effective_date',
        'status',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    /**
     * Get the product that owns the routing.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Get the routing operations for the routing.
     */
    public function routingOperations(): HasMany
    {
        return $this->hasMany(RoutingOperation::class, 'routing_id', 'routing_id');
    }

    /**
     * Get the work orders for the routing.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'routing_id', 'routing_id');
    }
}