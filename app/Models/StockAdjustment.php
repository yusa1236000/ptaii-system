<?php
// app/Models/StockAdjustment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $primaryKey = 'adjustment_id';
    protected $fillable = [
        'adjustment_date', 
        'adjustment_reason', 
        'status', 
        'reference_document'
    ];

    public function lines()
    {
        return $this->hasMany(StockAdjustmentLine::class, 'adjustment_id', 'adjustment_id');
    }
}