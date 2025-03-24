<?php
// app/Models/WarehouseLocation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $primaryKey = 'location_id';
    protected $fillable = ['zone_id', 'code', 'description'];

    public function zone()
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id', 'zone_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'location_id', 'location_id');
    }

    public function adjustmentLines()
    {
        return $this->hasMany(StockAdjustmentLine::class, 'location_id', 'location_id');
    }

    public function cycleCounts()
    {
        return $this->hasMany(CycleCounting::class, 'location_id', 'location_id');
    }
}