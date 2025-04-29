<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Item;
use App\Models\UnitOfMeasure;

class BOMLine extends Model
{
    use HasFactory;

    protected $table = 'bom_lines';
    protected $primaryKey = 'line_id';
    public $timestamps = false;

    protected $fillable = [
        'bom_id',
        'item_id',
        'quantity',
        'uom_id',
        'is_critical',
        'notes',
        'yield_ratio', // How many finished products can be produced from this material
        'is_yield_based', // Flag to indicate if this is a yield-based calculation
        'shrinkage_factor', // Optional: Factor to account for material waste/shrinkage
    ];

    protected $casts = [
        'is_critical' => 'boolean',
        'is_yield_based' => 'boolean',
        'yield_ratio' => 'float',
        'shrinkage_factor' => 'float',
    ];

    /**
     * Get the BOM that owns the BOM line.
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(BOM::class, 'bom_id', 'bom_id');
    }

    /**
     * Get the item that owns the BOM line.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the unit of measure that owns the BOM line.
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id', 'uom_id');
    }

    /**
     * Calculate the total yield of finished products from this material
     * 
     * @param float $materialQuantity The available quantity of this material
     * @return float The number of finished products that can be produced
     */
    public function calculateYield($materialQuantity = null)
    {
        // If no material quantity provided, use the quantity in BOM
        $materialQuantity = $materialQuantity ?? $this->quantity;
        
        if ($this->is_yield_based && $this->yield_ratio > 0) {
            // Apply shrinkage factor if defined
            $effectiveQuantity = $materialQuantity;
            if ($this->shrinkage_factor > 0) {
                $effectiveQuantity = $materialQuantity * (1 - $this->shrinkage_factor);
            }
            
            // Calculate how many finished products can be produced
            return $effectiveQuantity * $this->yield_ratio;
        }
        
        // Default behavior for non-yield-based items
        return null;
    }
}