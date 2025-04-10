<?php
// app/Models/Item.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $primaryKey = 'item_id';
    protected $fillable = [
        'item_code', 
        'name', 
        'description', 
        'category_id', 
        'uom_id', 
        'current_stock', 
        'minimum_stock', 
        'maximum_stock'
    ];

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id', 'category_id');
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id', 'uom_id');
    }

    public function batches()
    {
        return $this->hasMany(ItemBatch::class, 'item_id', 'item_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'item_id', 'item_id');
    }

    public function adjustmentLines()
    {
        return $this->hasMany(StockAdjustmentLine::class, 'item_id', 'item_id');
    }

    public function cycleCounts()
    {
        return $this->hasMany(CycleCounting::class, 'item_id', 'item_id');
    }
}