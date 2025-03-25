<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->string('invoice_number', 50)->unique();
            $table->date('invoice_date');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('po_id');
            $table->float('total_amount')->default(0);
            $table->float('tax_amount')->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamps();
            
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors');
            $table->foreign('po_id')->references('po_id')->on('purchase_orders');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_invoices');
    }
};