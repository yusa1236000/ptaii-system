<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentStock extends Model
{
    use HasFactory;

    protected $table = 'consignment_stocks';
    protected $primaryKey = 'consignment_id';
    protected $fillable = [
        'item_id', 
        'vendor_id', 
        'warehouse_id', 
        'quantity', 
        'received_date'
    ];

    protected $dates = [
        'received_date',
    ];

    /**
     * Get the item for this consignment
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the vendor for this consignment
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    /**
     * Get the warehouse for this consignment
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }
}