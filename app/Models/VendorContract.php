<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorContract extends Model
{
    use HasFactory;

    protected $table = 'vendor_contracts';
    protected $primaryKey = 'contract_id';
    protected $fillable = [
        'vendor_id',
        'contract_number',
        'start_date',
        'end_date',
        'contract_type',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}