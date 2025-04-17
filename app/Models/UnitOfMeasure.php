<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasFactory;

    protected $table = 'unit_of_measures';
    protected $primaryKey = 'uom_id';
    protected $fillable = ['name', 'symbol', 'description'];

    /**
     * Get the items using this unit of measure
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'uom_id', 'uom_id');
    }

    /**
     * Get conversions from this UOM
     */
    public function fromConversions()
    {
        return $this->hasMany(UOMConversion::class, 'from_uom_id', 'uom_id');
    }

    /**
     * Get conversions to this UOM
     */
    public function toConversions()
    {
        return $this->hasMany(UOMConversion::class, 'to_uom_id', 'uom_id');
    }
}