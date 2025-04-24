<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FixedAsset';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'asset_id';

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
        'asset_code',
        'name',
        'category',
        'acquisition_date',
        'acquisition_cost',
        'current_value',
        'depreciation_rate',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_cost' => 'float',
        'current_value' => 'float',
        'depreciation_rate' => 'float',
    ];

    /**
     * Get the asset depreciations for the fixed asset.
     */
    public function assetDepreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class, 'asset_id');
    }
}