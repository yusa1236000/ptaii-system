<?php
// app/Models/StockTransaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaction_id';
    protected $fillable = [
        'item_id', 
        'warehouse_id', 
        'location_id', 
        'transaction_type', 
        'quantity', 
        'transaction_date', 
        'reference_document', 
        'reference_number', 
        'batch_id'
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

    public function batch()
    {
        return $this->belongsTo(ItemBatch::class, 'batch_id', 'batch_id');
    }
}