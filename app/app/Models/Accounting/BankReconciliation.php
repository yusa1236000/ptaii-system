<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'BankReconciliation';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'reconciliation_id';

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
        'bank_id',
        'statement_date',
        'statement_balance',
        'book_balance',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'float',
        'book_balance' => 'float',
    ];

    /**
     * Get the bank account that owns the reconciliation.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_id');
    }

    /**
     * Get the reconciliation lines for the bank reconciliation.
     */
    public function reconciliationLines(): HasMany
    {
        return $this->hasMany(BankReconciliationLine::class, 'reconciliation_id');
    }
}