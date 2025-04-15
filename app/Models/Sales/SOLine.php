<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Item;
use App\Models\UnitOfMeasure;

class SOLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    protected $table = 'SOLine';
    public $timestamps = false;

    protected $fillable = [
        'so_id',
        'item_id',
        'unit_price',
        'quantity',
        'uom_id',
        'discount',
        'subtotal',
        'tax',
        'total'
    ];

    /**
     * Get the sales order that owns the sales order line.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    /**
     * Get the item that the sales order line belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get the unit of measure that the sales order line belongs to.
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    /**
     * Get the delivery lines for the sales order line.
     */
    public function deliveryLines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class, 'so_line_id');
    }

    /**
     * Get the sales invoice lines for the sales order line.
     */
    public function salesInvoiceLines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'so_line_id');
    }
}