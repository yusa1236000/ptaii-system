<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    public $timestamps = false;
    
    protected $fillable = [
        'return_id',
        'invoice_line_id',
        'item_id',
        'returned_quantity',
        'condition'
    ];

    /**
     * Get the sales return that owns the sales return line.
     */
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class, 'return_id');
    }

    /**
     * Get the sales invoice line that owns the sales return line.
     */
    public function salesInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceLine::class, 'invoice_line_id');
    }

    /**
     * Get the item that the sales return line belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}