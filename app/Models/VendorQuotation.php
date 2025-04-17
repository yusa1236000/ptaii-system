<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorQuotation extends Model
{
    use HasFactory;

    protected $table = 'vendor_quotations';
    protected $primaryKey = 'quotation_id';
    protected $fillable = [
        'rfq_id',
        'vendor_id',
        'quotation_date',
        'validity_date',
        'status'
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'validity_date' => 'date',
    ];

    public function requestForQuotation()
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function lines()
    {
        return $this->hasMany(VendorQuotationLine::class, 'quotation_id');
    }
}