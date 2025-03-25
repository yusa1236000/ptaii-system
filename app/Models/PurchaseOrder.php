<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $primaryKey = 'po_id';
    
    protected $fillable = [
        'po_number',
        'po_date',
        'vendor_id',
        'payment_terms',
        'delivery_terms',
        'expected_delivery',
        'status',
        'total_amount',
        'tax_amount'
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function lines()
    {
        return $this->hasMany(POLine::class, 'po_id');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class, 'po_id');
    }

    public function invoices()
    {
        return $this->hasMany(VendorInvoice::class, 'po_id');
    }
}