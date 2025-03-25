<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorInvoice extends Model
{
    use HasFactory;

    protected $primaryKey = 'invoice_id';
    
    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'vendor_id',
        'po_id',
        'total_amount',
        'tax_amount',
        'due_date',
        'status'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function lines()
    {
        return $this->hasMany(VendorInvoiceLine::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(PayablePayment::class, 'invoice_id');
    }
}