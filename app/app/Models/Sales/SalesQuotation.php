<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesQuotation extends Model
{
    use HasFactory;

    protected $table = 'SalesQuotation';
    protected $primaryKey = 'quotation_id';
    public $timestamps = false;


    protected $fillable = [
        'quotation_number',
        'quotation_date',
        'customer_id',
        'validity_date',
        'status',
        'payment_terms',
        'delivery_terms'
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'validity_date' => 'date',
    ];

    /**
     * Get the customer that owns the sales quotation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the sales quotation lines for the sales quotation.
     */
    public function salesQuotationLines(): HasMany
    {
        return $this->hasMany(SalesQuotationLine::class, 'quotation_id');
    }

    /**
     * Get the sales orders for the sales quotation.
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'quotation_id');
    }
}
