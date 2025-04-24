<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustments';
    protected $primaryKey = 'adjustment_id';
    protected $fillable = [
        'adjustment_date', 
        'adjustment_reason', 
        'status', 
        'reference_document'
    ];

    protected $dates = [
        'adjustment_date',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    /**
     * Get the adjustment lines for this adjustment
     */
    public function adjustmentLines()
    {
        return $this->hasMany(StockAdjustmentLine::class, 'adjustment_id', 'adjustment_id');
    }

    /**
     * Get the total variance quantity
     */
    public function getTotalVarianceAttribute()
    {
        return $this->adjustmentLines->sum('variance');
    }

    /**
     * Process the stock adjustment by creating stock transactions
     */
    public function process()
    {
        if ($this->status != self::STATUS_APPROVED) {
            return false;
        }

        foreach ($this->adjustmentLines as $line) {
            // Create a stock transaction for each adjustment line
            StockTransaction::create([
                'item_id' => $line->item_id,
                'warehouse_id' => $line->warehouse_id,
                'location_id' => $line->location_id,
                'transaction_type' => StockTransaction::TYPE_ADJUSTMENT,
                'quantity' => $line->variance, // The variance can be positive or negative
                'transaction_date' => $this->adjustment_date,
                'reference_document' => 'stock_adjustment',
                'reference_number' => $this->adjustment_id
            ]);

            // Update the item's current stock
            $item = Item::find($line->item_id);
            $item->current_stock += $line->variance;
            $item->save();
        }

        // Update the adjustment status
        $this->status = self::STATUS_COMPLETED;
        $this->save();

        return true;
    }
}