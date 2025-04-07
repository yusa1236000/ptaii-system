<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Product Table
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('uom_id');
            $table->timestamps();
            
            $table->foreign('category_id')->references('category_id')->on('item_categories');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });

        // BOM Table
        Schema::create('boms', function (Blueprint $table) {
            $table->id('bom_id');
            $table->unsignedBigInteger('product_id');
            $table->string('bom_code', 50);
            $table->string('revision', 10);
            $table->date('effective_date');
            $table->string('status', 50);
            $table->float('standard_quantity');
            $table->unsignedBigInteger('uom_id');
            $table->timestamps();
            
            $table->foreign('product_id')->references('product_id')->on('products');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });

        // BOM Line Table
        Schema::create('bom_lines', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('bom_id');
            $table->unsignedBigInteger('item_id');
            $table->float('quantity');
            $table->unsignedBigInteger('uom_id');
            $table->boolean('is_critical');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('bom_id')->references('bom_id')->on('boms');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });

        // Routing Table
        Schema::create('routings', function (Blueprint $table) {
            $table->id('routing_id');
            $table->unsignedBigInteger('product_id');
            $table->string('routing_code', 50);
            $table->string('revision', 10);
            $table->date('effective_date');
            $table->string('status', 50);
            $table->timestamps();
            
            $table->foreign('product_id')->references('product_id')->on('products');
        });

        // Work Center Table
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id('workcenter_id');
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->float('capacity');
            $table->float('cost_per_hour');
            $table->boolean('is_active');
            $table->timestamps();
        });

        // Routing Operation Table
        Schema::create('routing_operations', function (Blueprint $table) {
            $table->id('operation_id');
            $table->unsignedBigInteger('routing_id');
            $table->unsignedBigInteger('workcenter_id');
            $table->string('operation_name', 100);
            $table->integer('sequence');
            $table->float('setup_time');
            $table->float('run_time');
            $table->unsignedBigInteger('uom_id');
            $table->float('labor_cost');
            $table->float('overhead_cost');
            $table->timestamps();
            
            $table->foreign('routing_id')->references('routing_id')->on('routings');
            $table->foreign('workcenter_id')->references('workcenter_id')->on('work_centers');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });

        // Work Order Table
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id('wo_id');
            $table->string('wo_number', 50)->unique();
            $table->date('wo_date');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('bom_id');
            $table->unsignedBigInteger('routing_id');
            $table->float('planned_quantity');
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->string('status', 50);
            $table->timestamps();
            
            $table->foreign('product_id')->references('product_id')->on('products');
            $table->foreign('bom_id')->references('bom_id')->on('boms');
            $table->foreign('routing_id')->references('routing_id')->on('routings');
        });

        // Work Order Operation Table
        Schema::create('work_order_operations', function (Blueprint $table) {
            $table->id('operation_id');
            $table->unsignedBigInteger('wo_id');
            $table->unsignedBigInteger('routing_operation_id');
            $table->date('scheduled_start');
            $table->date('scheduled_end');
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->float('actual_labor_time')->nullable();
            $table->float('actual_machine_time')->nullable();
            $table->string('status', 50);
            $table->timestamps();
            
            $table->foreign('wo_id')->references('wo_id')->on('work_orders');
            $table->foreign('routing_operation_id')->references('operation_id')->on('routing_operations');
        });

        // Production Order Table
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id('production_id');
            $table->unsignedBigInteger('wo_id');
            $table->string('production_number', 50)->unique();
            $table->date('production_date');
            $table->float('planned_quantity');
            $table->float('actual_quantity')->nullable();
            $table->string('status', 50);
            $table->timestamps();
            
            $table->foreign('wo_id')->references('wo_id')->on('work_orders');
        });

        // Production Consumption Table
        Schema::create('production_consumptions', function (Blueprint $table) {
            $table->id('consumption_id');
            $table->unsignedBigInteger('production_id');
            $table->unsignedBigInteger('item_id');
            $table->float('planned_quantity');
            $table->float('actual_quantity')->nullable();
            $table->float('variance')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id');
            $table->timestamps();
            
            $table->foreign('production_id')->references('production_id')->on('production_orders');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses');
            $table->foreign('location_id')->references('location_id')->on('warehouse_locations');
        });

        // Quality Inspection Table
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id('inspection_id');
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->date('inspection_date');
            $table->string('status', 50);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Quality Parameter Table
        Schema::create('quality_parameters', function (Blueprint $table) {
            $table->id('parameter_id');
            $table->unsignedBigInteger('inspection_id');
            $table->string('parameter_name', 100);
            $table->text('specification');
            $table->text('actual_value');
            $table->boolean('is_passed');
            $table->timestamps();
            
            $table->foreign('inspection_id')->references('inspection_id')->on('quality_inspections');
        });

        // Maintenance Schedule Table
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->unsignedBigInteger('workcenter_id');
            $table->string('maintenance_type', 50);
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            $table->string('status', 50);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('workcenter_id')->references('workcenter_id')->on('work_centers');
        });
    }

    public function down()
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('quality_parameters');
        Schema::dropIfExists('quality_inspections');
        Schema::dropIfExists('production_consumptions');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('work_order_operations');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('routing_operations');
        Schema::dropIfExists('work_centers');
        Schema::dropIfExists('routings');
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('boms');
        Schema::dropIfExists('products');
    }
};