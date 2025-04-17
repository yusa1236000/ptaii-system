<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BOM extends Model
{
    use HasFactory;

    protected $table = 'boms';
    protected $primaryKey = 'bom_id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'bom_code',
        'revision',
        'effective_date',
        'status',
        'standard_quantity',
        'uom_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    /**
     * Get the product that owns the BOM.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Get the unit of measure that owns the BOM.
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id', 'uom_id');
    }

    /**
     * Get the BOM lines for the BOM.
     */
    public function bomLines(): HasMany
    {
        return $this->hasMany(BOMLine::class, 'bom_id', 'bom_id');
    }

    /**
     * Get the work orders for the BOM.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'bom_id', 'bom_id');
    }
}