<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CycleCounting extends Model
{
    use HasFactory;

    protected $table = 'cycle_countings';
    protected $primaryKey = 'count_id';
    protected $fillable = [
        'item_id', 
        'warehouse_id', 
        //'location_id', 
        'book_quantity', 
        'actual_quantity', 
        'variance', 
        'count_date', 
        'status'
    ];

    protected $dates = [
        'count_date',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    /**
     * Get the item being counted
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the warehouse for this count
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    /**
     * Get the location for this count
     */
    // public function location()
    // {
    //     return $this->belongsTo(WarehouseLocation::class, 'location_id', 'location_id');
    // }

    /**
     * Calculate the variance percentage
     */
    public function getVariancePercentageAttribute()
    {
        if ($this->book_quantity == 0) {
            return null; // Avoid division by zero
        }
        
        return ($this->variance / $this->book_quantity) * 100;
    }

    /**
     * Determine if the variance is significant
     */
    public function isVarianceSignificant($threshold = 5.0)
    {
        $percentage = $this->getVariancePercentageAttribute();
        
        if ($percentage === null) {
            return false;
        }
        
        return abs($percentage) >= $threshold;
    }
}