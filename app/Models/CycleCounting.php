<?php
// app/Models/CycleCounting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CycleCounting extends Model
{
    use HasFactory;

    protected $primaryKey = 'count_id';
    protected $fillable = [
        'item_id', 
        'warehouse_id', 
        'location_id', 
        'book_quantity', 
        'actual_quantity', 
        'variance', 
        'count_date', 
        'status'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id', 'location_id');
    }
}