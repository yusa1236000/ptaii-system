<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $primaryKey = 'invoice_id';
    public $timestamps = false;
    
    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'customer_id',
        'so_id',
        'total_amount',
        'tax_amount',
        'due_date',
        'status'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Get the customer that owns the sales invoice.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the sales order that owns the sales invoice.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    /**
     * Get the sales invoice lines for the sales invoice.
     */
    public function salesInvoiceLines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'invoice_id');
    }

    /**
     * Get the sales returns for the sales invoice.
     */
    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'invoice_id');
    }

    /**
     * Get the customer receivables for the sales invoice.
     */
    public function customerReceivables(): HasMany
    {
        return $this->hasMany(CustomerReceivable::class, 'invoice_id');
    }

    /**
     * Get the sales commissions for the sales invoice.
     */
    public function salesCommissions(): HasMany
    {
        return $this->hasMany(SalesCommission::class, 'invoice_id');
    }
}