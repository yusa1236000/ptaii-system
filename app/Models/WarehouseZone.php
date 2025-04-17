<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseZone extends Model
{
    use HasFactory;

    protected $table = 'warehouse_zones';
    protected $primaryKey = 'zone_id';
    protected $fillable = ['warehouse_id', 'name', 'code', 'description'];

    /**
     * Get the warehouse that owns this zone
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    /**
     * Get the locations in this zone
     */
    public function locations()
    {
        return $this->hasMany(WarehouseLocation::class, 'zone_id', 'zone_id');
    }
}