<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Item; // Tambahkan import ini
use App\Models\UnitOfMeasure; // Tambahkan import ini

class BOM extends Model
{
    use HasFactory;

    protected $table = 'boms';
    protected $primaryKey = 'bom_id';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
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
     * Get the item that owns the BOM.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
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