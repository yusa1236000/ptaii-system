<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    
    protected $fillable = [
        'receipt_id',
        'po_line_id',
        'item_id',
        'received_quantity',
        'warehouse_id',
        'location_id',
        'batch_number'
    ];

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'receipt_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(POLine::class, 'po_line_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }
}