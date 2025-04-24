<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasFactory;

    protected $table = 'SalesReturn';
    protected $primaryKey = 'return_id';
    public $timestamps = false;
    
    protected $fillable = [
        'return_number',
        'return_date',
        'customer_id',
        'invoice_id',
        'return_reason',
        'status'
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    /**
     * Get the customer that owns the sales return.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the sales invoice that owns the sales return.
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    /**
     * Get the sales return lines for the sales return.
     */
    public function salesReturnLines(): HasMany
    {
        return $this->hasMany(SalesReturnLine::class, 'return_id');
    }
}