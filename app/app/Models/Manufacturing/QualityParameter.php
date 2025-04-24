<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityParameter extends Model
{
    use HasFactory;

    protected $table = 'QualityParameter';
    protected $primaryKey = 'parameter_id';
    public $timestamps = false;

    protected $fillable = [
        'inspection_id',
        'parameter_name',
        'specification',
        'actual_value',
        'is_passed',
    ];

    protected $casts = [
        'is_passed' => 'boolean',
    ];

    /**
     * Get the quality inspection that owns the quality parameter.
     */
    public function qualityInspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'inspection_id', 'inspection_id');
    }
}