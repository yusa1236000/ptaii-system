<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $table = 'warehouse_locations';
    protected $primaryKey = 'location_id';
    protected $fillable = ['zone_id', 'code', 'description'];

    /**
     * Get the zone that owns this location
     */
    public function zone()
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id', 'zone_id');
    }

    /**
     * Get the warehouse through zone
     */
    public function warehouse()
    {
        return $this->hasOneThrough(
            Warehouse::class,
            WarehouseZone::class,
            'zone_id', // Foreign key on warehouse_zones
            'warehouse_id', // Foreign key on warehouses
            'zone_id', // Local key on warehouse_locations
            'warehouse_id' // Local key on warehouse_zones
        );
    }

    /**
     * Get the stock transactions for this location
     */
    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'location_id', 'location_id');
    }
}