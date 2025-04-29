<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends Model
{
    use HasFactory;

    protected $table = 'work_centers';
    protected $primaryKey = 'workcenter_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'capacity',
        'cost_per_hour',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the routing operations for the work center.
     */
    public function routingOperations(): HasMany
    {
        return $this->hasMany(RoutingOperation::class, 'workcenter_id', 'workcenter_id');
    }

    /**
     * Get the maintenance schedules for the work center.
     */
    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class, 'workcenter_id', 'workcenter_id');
    }
}