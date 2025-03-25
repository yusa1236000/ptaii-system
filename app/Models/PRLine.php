<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PRLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    
    protected $fillable = [
        'pr_id',
        'item_id',
        'quantity',
        'uom_id',
        'required_date',
        'notes'
    ];

    protected $casts = [
        'required_date' => 'date',
    ];

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class, 'pr_id');
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