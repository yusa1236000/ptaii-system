<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('po_lines', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('po_id');
            $table->unsignedBigInteger('item_id');
            $table->float('unit_price');
            $table->float('quantity');
            $table->unsignedBigInteger('uom_id');
            $table->float('subtotal')->default(0);
            $table->float('tax')->default(0);
            $table->float('total')->default(0);
            $table->timestamps();
            
            $table->foreign('po_id')->references('po_id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });
    }

    public function down()
    {
        Schema::dropIfExists('po_lines');
    }
};