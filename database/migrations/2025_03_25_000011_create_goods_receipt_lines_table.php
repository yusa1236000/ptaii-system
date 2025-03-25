<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('receipt_id');
            $table->unsignedBigInteger('po_line_id');
            $table->unsignedBigInteger('item_id');
            $table->float('received_quantity');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('batch_number', 50)->nullable();
            $table->timestamps();
            
            $table->foreign('receipt_id')->references('receipt_id')->on('goods_receipts')->onDelete('cascade');
            $table->foreign('po_line_id')->references('line_id')->on('po_lines');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses');
            $table->foreign('location_id')->references('location_id')->on('warehouse_locations');
        });
    }

    public function down()
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};