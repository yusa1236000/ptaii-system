<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_quotation_lines', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('item_id');
            $table->float('unit_price');
            $table->unsignedBigInteger('uom_id');
            $table->float('quantity');
            $table->date('delivery_date')->nullable();
            $table->timestamps();
            
            $table->foreign('quotation_id')->references('quotation_id')->on('vendor_quotations')->onDelete('cascade');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_quotation_lines');
    }
};