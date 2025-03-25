<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id('po_id');
            $table->string('po_number', 50)->unique();
            $table->date('po_date');
            $table->unsignedBigInteger('vendor_id');
            $table->text('payment_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->date('expected_delivery')->nullable();
            $table->string('status', 50)->default('draft');
            $table->float('total_amount')->default(0);
            $table->float('tax_amount')->default(0);
            $table->timestamps();
            
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
};