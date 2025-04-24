<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPayable extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'VendorPayable';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'payable_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vendor_id',
        'invoice_id',
        'amount',
        'due_date',
        'paid_amount',
        'balance',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'paid_amount' => 'float',
        'balance' => 'float',
        'due_date' => 'date',
    ];

    /**
     * Get the vendor that owns the payable.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the vendor invoice that owns the payable.
     */
    public function vendorInvoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class, 'invoice_id');
    }

    /**
     * Get the payable payments for the payable.
     */
    public function payablePayments(): HasMany
    {
        return $this->hasMany(PayablePayment::class, 'payable_id');
    }
}