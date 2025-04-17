<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UOMConversion extends Model
{
    use HasFactory;

    protected $table = 'uom_conversions';
    protected $primaryKey = 'conversion_id';
    protected $fillable = ['from_uom_id', 'to_uom_id', 'conversion_factor'];

    /**
     * Get the source unit of measure
     */
    public function fromUOM()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'from_uom_id', 'uom_id');
    }

    /**
     * Get the target unit of measure
     */
    public function toUOM()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'to_uom_id', 'uom_id');
    }
    
    /**
     * Convert a quantity from the source UOM to the target UOM
     */
    public function convert($quantity)
    {
        return $quantity * $this->conversion_factor;
    }
}