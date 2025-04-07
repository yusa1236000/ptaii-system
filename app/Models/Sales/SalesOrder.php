<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasFactory;

    protected $primaryKey = 'so_id';
    public $timestamps = false;
    
    protected $fillable = [
        'so_number',
        'so_date',
        'customer_id',
        'quotation_id',
        'payment_terms',
        'delivery_terms',
        'expected_delivery',
        'status',
        'total_amount',
        'tax_amount'
    ];

    protected $casts = [
        'so_date' => 'date',
        'expected_delivery' => 'date',
    ];

    /**
     * Get the customer that owns the sales order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the sales quotation that owns the sales order.
     */
    public function salesQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'quotation_id');
    }

    /**
     * Get the sales order lines for the sales order.
     */
    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SOLine::class, 'so_id');
    }

    /**
     * Get the deliveries for the sales order.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'so_id');
    }

    /**
     * Get the sales invoices for the sales order.
     */
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'so_id');
    }
}