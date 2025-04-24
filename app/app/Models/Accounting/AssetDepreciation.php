<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'AssetDepreciation';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'depreciation_id';

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
        'asset_id',
        'period_id',
        'depreciation_date',
        'depreciation_amount',
        'accumulated_depreciation',
        'remaining_value'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'depreciation_date' => 'date',
        'depreciation_amount' => 'float',
        'accumulated_depreciation' => 'float',
        'remaining_value' => 'float',
    ];

    /**
     * Get the fixed asset that owns the depreciation.
     */
    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    /**
     * Get the accounting period that owns the depreciation.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }
}