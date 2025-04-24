<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorQuotationLine extends Model
{
    use HasFactory;

    protected $table = 'vendor_quotation_lines';
    protected $primaryKey = 'line_id';
    protected $fillable = [
        'quotation_id',
        'item_id',
        'unit_price',
        'uom_id',
        'quantity',
        'delivery_date'
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function vendorQuotation()
    {
        return $this->belongsTo(VendorQuotation::class, 'quotation_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }
}