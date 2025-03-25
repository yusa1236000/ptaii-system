<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_evaluations', function (Blueprint $table) {
            $table->id('evaluation_id');
            $table->unsignedBigInteger('vendor_id');
            $table->date('evaluation_date');
            $table->float('quality_score')->default(0);
            $table->float('delivery_score')->default(0);
            $table->float('price_score')->default(0);
            $table->float('service_score')->default(0);
            $table->float('total_score')->default(0);
            $table->timestamps();
            
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_evaluations');
    }
};