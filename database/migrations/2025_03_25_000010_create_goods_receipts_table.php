<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id('receipt_id');
            $table->string('receipt_number', 50)->unique();
            $table->date('receipt_date');
            $table->unsignedBigInteger('po_id');
            $table->unsignedBigInteger('vendor_id');
            $table->string('status', 50)->default('draft');
            $table->timestamps();
            
            $table->foreign('po_id')->references('po_id')->on('purchase_orders');
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors');
        });
    }

    public function down()
    {
        Schema::dropIfExists('goods_receipts');
    }
};