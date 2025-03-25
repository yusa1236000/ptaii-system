<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_invoice_lines', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('po_line_id');
            $table->unsignedBigInteger('item_id');
            $table->float('quantity');
            $table->float('unit_price');
            $table->float('subtotal')->default(0);
            $table->float('tax')->default(0);
            $table->float('total')->default(0);
            $table->timestamps();
            
            $table->foreign('invoice_id')->references('invoice_id')->on('vendor_invoices')->onDelete('cascade');
            $table->foreign('po_line_id')->references('line_id')->on('po_lines');
            $table->foreign('item_id')->references('item_id')->on('items');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_invoice_lines');
    }
};