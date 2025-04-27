// database/migrations/create_material_plans_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialPlansTable extends Migration
{
    public function up()
    {
        Schema::create('material_plans', function (Blueprint $table) {
            $table->id('plan_id');
            $table->foreignId('item_id')->constrained('items', 'item_id');
            $table->date('planning_period');
            $table->enum('material_type', ['FG', 'RM', 'WIP'])->default('RM');
            $table->foreignId('bom_id')->nullable()->constrained('boms', 'bom_id');
            $table->decimal('forecast_quantity', 12, 2)->default(0);
            $table->decimal('available_stock', 12, 2)->default(0);
            $table->decimal('wip_stock', 12, 2)->default(0);
            $table->decimal('buffer_percentage', 5, 2)->default(0);
            $table->decimal('buffer_quantity', 12, 2)->default(0);
            $table->decimal('net_requirement', 12, 2)->default(0);
            $table->decimal('planned_order_quantity', 12, 2)->default(0);
            $table->string('status', 50)->default('Draft');
            $table->timestamps();
            
            $table->unique(['item_id', 'planning_period', 'material_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('material_plans');
    }
}