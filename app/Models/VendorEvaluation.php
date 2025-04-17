<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorEvaluation extends Model
{
    use HasFactory;

    protected $table = 'vendor_evaluations';
    protected $primaryKey = 'evaluation_id';
    protected $fillable = [
        'vendor_id',
        'evaluation_date',
        'quality_score',
        'delivery_score',
        'price_score',
        'service_score',
        'total_score'
    ];

    protected $casts = [
        'evaluation_date' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}