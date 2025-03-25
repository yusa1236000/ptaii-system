<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_quotations', function (Blueprint $table) {
            $table->id('quotation_id');
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('vendor_id');
            $table->date('quotation_date');
            $table->date('validity_date')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamps();
            
            $table->foreign('rfq_id')->references('rfq_id')->on('request_for_quotations');
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_quotations');
    }
};