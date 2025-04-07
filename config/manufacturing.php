<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Manufacturing Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the Manufacturing module.
    |
    */

    // Work Order Status Options
    'wo_status_options' => [
        'Draft',
        'Planned',
        'Released',
        'In Progress',
        'Completed',
        'Closed',
        'Cancelled',
    ],

    // Production Order Status Options
    'production_status_options' => [
        'Draft',
        'Planned',
        'In Progress',
        'Completed',
        'Cancelled',
    ],

    // Quality Inspection Status Options
    'quality_status_options' => [
        'Planned',
        'In Progress',
        'Passed',
        'Failed',
        'Hold',
    ],

    // Maintenance Schedule Type Options
    'maintenance_types' => [
        'Preventive',
        'Corrective',
        'Predictive',
        'Breakdown',
        'Safety',
    ],

    // Default Warehouse for Production
    'default_warehouse_id' => env('DEFAULT_WAREHOUSE_ID', 1),

    // Enable Batch Tracking
    'enable_batch_tracking' => env('ENABLE_BATCH_TRACKING', true),

    // Enable Work Order Operation Labor Tracking
    'enable_labor_tracking' => env('ENABLE_LABOR_TRACKING', true),

    // Enable Quality Inspection for Production
    'enable_quality_inspection' => env('ENABLE_QUALITY_INSPECTION', true),

];