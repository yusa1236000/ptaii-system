<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ItemStock', function (Blueprint $table) {
            $table->id('stock_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('reserved_quantity', 15, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('Warehouse');
            
            // Unique constraint untuk memastikan satu item hanya memiliki satu record per warehouse
            $table->unique(['item_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ItemStock');
    }
}