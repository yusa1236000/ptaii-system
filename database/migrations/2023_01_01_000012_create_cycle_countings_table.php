<?php
// database/migrations/2023_01_01_000012_create_cycle_countings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cycle_countings', function (Blueprint $table) {
            $table->id('count_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->float('book_quantity');
            $table->float('actual_quantity');
            $table->float('variance');
            $table->date('count_date');
            $table->string('status', 50);
            $table->timestamps();
            
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses');
            $table->foreign('location_id')->references('location_id')->on('warehouse_locations');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cycle_countings');
    }
};