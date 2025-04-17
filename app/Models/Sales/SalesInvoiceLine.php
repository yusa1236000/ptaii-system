<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoiceLine extends Model
{
    use HasFactory;

    protected $table = 'SalesInvoiceLine';
    protected $primaryKey = 'line_id';
    public $timestamps = false;
    
    protected $fillable = [
        'invoice_id',
        'so_line_id',
        'item_id',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
        'tax',
        'total'
    ];

    /**
     * Get the sales invoice that owns the sales invoice line.
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    /**
     * Get the sales order line that owns the sales invoice line.
     */
    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SOLine::class, 'so_line_id');
    }

    /**
     * Get the item that the sales invoice line belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get the sales return lines for the sales invoice line.
     */
    public function salesReturnLines(): HasMany
    {
        return $this->hasMany(SalesReturnLine::class, 'invoice_line_id');
    }
}