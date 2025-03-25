<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $primaryKey = 'receipt_id';
    
    protected $fillable = [
        'receipt_number',
        'receipt_date',
        'po_id',
        'vendor_id',
        'status'
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function lines()
    {
        return $this->hasMany(GoodsReceiptLine::class, 'receipt_id');
    }
}