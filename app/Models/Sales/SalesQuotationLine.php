<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    public $timestamps = false;
    
    protected $fillable = [
        'quotation_id',
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
     * Get the sales quotation that owns the sales quotation line.
     */
    public function salesQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'quotation_id');
    }

    /**
     * Get the item that the sales quotation line belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get the unit of measure that the sales quotation line belongs to.
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }
}