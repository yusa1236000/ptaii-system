<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorInvoiceLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    
    protected $fillable = [
        'invoice_id',
        'po_line_id',
        'item_id',
        'quantity',
        'unit_price',
        'subtotal',
        'tax',
        'total'
    ];

    public function invoice()
    {
        return $this->belongsTo(VendorInvoice::class, 'invoice_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(POLine::class, 'po_line_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}