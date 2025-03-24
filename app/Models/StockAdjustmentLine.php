<?php
// app/Models/StockAdjustmentLine.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    protected $fillable = [
        'adjustment_id', 
        'item_id', 
        'warehouse_id', 
        'location_id', 
        'book_quantity', 
        'adjusted_quantity', 
        'variance'
    ];

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id', 'adjustment_id');
    }

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