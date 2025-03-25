<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    
    protected $fillable = [
        'po_id',
        'item_id',
        'unit_price',
        'quantity',
        'uom_id',
        'subtotal',
        'tax',
        'total'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function goodsReceiptLines()
    {
        return $this->hasMany(GoodsReceiptLine::class, 'po_line_id');
    }

    public function invoiceLines()
    {
        return $this->hasMany(VendorInvoiceLine::class, 'po_line_id');
    }
}