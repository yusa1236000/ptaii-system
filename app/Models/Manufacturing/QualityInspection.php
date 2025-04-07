<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityInspection extends Model
{
    use HasFactory;

    protected $table = 'QualityInspection';
    protected $primaryKey = 'inspection_id';
    public $timestamps = false;

    protected $fillable = [
        'reference_type',
        'reference_id',
        'inspection_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'inspection_date' => 'date',
    ];

    /**
     * Get the quality parameters for the quality inspection.
     */
    public function qualityParameters(): HasMany
    {
        return $this->hasMany(QualityParameter::class, 'inspection_id', 'inspection_id');
    }
}