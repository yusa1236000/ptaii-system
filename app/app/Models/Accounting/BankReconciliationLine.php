<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationLine extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'BankReconciliationLine';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'line_id';

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
        'reconciliation_id',
        'transaction_type',
        'transaction_id',
        'transaction_date',
        'amount',
        'is_reconciled'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'float',
        'is_reconciled' => 'boolean',
    ];

    /**
     * Get the bank reconciliation that owns the line.
     */
    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }
}