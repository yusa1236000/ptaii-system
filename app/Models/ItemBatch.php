<?php
// app/Models/ItemBatch.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemBatch extends Model
{
    use HasFactory;

    protected $primaryKey = 'batch_id';
    protected $fillable = [
        'item_id', 
        'batch_number', 
        'expiry_date', 
        'manufacturing_date', 
        'lot_number'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'batch_id', 'batch_id');
    }
}