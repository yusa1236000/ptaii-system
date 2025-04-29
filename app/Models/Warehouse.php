<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';
    protected $primaryKey = 'warehouse_id';
    protected $fillable = ['name', 'code', 'address', 'is_active'];

    /**
     * Get the zones for this warehouse
     */
    // public function zones()
    // {
    //     return $this->hasMany(WarehouseZone::class, 'warehouse_id', 'warehouse_id');
    // }

    /**
     * Get the stock transactions for this warehouse
     */
    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'warehouse_id', 'warehouse_id');
    }

    /**
     * Get all locations in this warehouse through zones
     */
    // public function locations()
    // {
    //     return $this->hasManyThrough(
    //         WarehouseLocation::class,
    //         WarehouseZone::class,
    //         'warehouse_id', // Foreign key on warehouse_zones
    //         'zone_id', // Foreign key on warehouse_locations
    //         'warehouse_id', // Local key on warehouses
    //         'zone_id' // Local key on warehouse_zones
    //     );
    // }

    /**
     * Get consignment stocks in this warehouse
     */
    public function consignmentStocks()
    {
        return $this->hasMany(ConsignmentStock::class, 'warehouse_id', 'warehouse_id');
    }
}