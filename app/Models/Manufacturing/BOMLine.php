<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BOMLine extends Model
{
    use HasFactory;

    protected $table = 'BOMLine';
    protected $primaryKey = 'line_id';
    public $timestamps = false;

    protected $fillable = [
        'bom_id',
        'item_id',
        'quantity',
        'uom_id',
        'is_critical',
        'notes',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
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
}