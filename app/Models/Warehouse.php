<?php
// app/Models/Warehouse.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $primaryKey = 'warehouse_id';
    protected $fillable = ['name', 'code', 'address', 'is_active'];

    public function zones()
    {
        return $this->hasMany(WarehouseZone::class, 'warehouse_id', 'warehouse_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'warehouse_id', 'warehouse_id');
    }

    public function adjustmentLines()
    {
        return $this->hasMany(StockAdjustmentLine::class, 'warehouse_id', 'warehouse_id');
    }

    public function cycleCounts()
    {
        return $this->hasMany(CycleCounting::class, 'warehouse_id', 'warehouse_id');
    }
}