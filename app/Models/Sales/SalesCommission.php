<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCommission extends Model
{
    use HasFactory;

    protected $primaryKey = 'commission_id';
    public $timestamps = false;
    
    protected $fillable = [
        'sales_person_id',
        'invoice_id',
        'commission_amount',
        'calculation_date',
        'status'
    ];

    protected $casts = [
        'calculation_date' => 'date',
    ];

    /**
     * Get the sales person (user) that owns the sales commission.
     */
    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    /**
     * Get the sales invoice that owns the sales commission.
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }
}