<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RFQLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    
    protected $fillable = [
        'rfq_id',
        'item_id',
        'quantity',
        'uom_id',
        'required_date'
    ];

    protected $casts = [
        'required_date' => 'date',
    ];

    public function requestForQuotation()
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
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