<?php
// app/Models/WarehouseZone.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseZone extends Model
{
    use HasFactory;

    protected $primaryKey = 'zone_id';
    protected $fillable = ['warehouse_id', 'name', 'code', 'description'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function locations()
    {
        return $this->hasMany(WarehouseLocation::class, 'zone_id', 'zone_id');
    }
}