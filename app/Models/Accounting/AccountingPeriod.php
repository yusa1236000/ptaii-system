<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'AccountingPeriod';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'period_id';

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
        'period_name',
        'start_date',
        'end_date',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the journal entries for the accounting period.
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'period_id');
    }

    /**
     * Get the asset depreciations for the accounting period.
     */
    public function assetDepreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class, 'period_id');
    }

    /**
     * Get the budgets for the accounting period.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'period_id');
    }
}