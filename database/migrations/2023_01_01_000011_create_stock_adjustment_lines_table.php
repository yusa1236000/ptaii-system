<?php
// database/migrations/2023_01_01_000011_create_stock_adjustment_lines_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('adjustment_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->float('book_quantity');
            $table->float('adjusted_quantity');
            $table->float('variance');
            $table->timestamps();
            
            $table->foreign('adjustment_id')->references('adjustment_id')->on('stock_adjustments');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses');
            $table->foreign('location_id')->references('location_id')->on('warehouse_locations');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_adjustment_lines');
    }
};