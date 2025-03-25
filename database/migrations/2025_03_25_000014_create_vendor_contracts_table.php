<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_contracts', function (Blueprint $table) {
            $table->id('contract_id');
            $table->unsignedBigInteger('vendor_id');
            $table->string('contract_number', 50)->unique();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('contract_type', 50)->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamps();
            
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_contracts');
    }
};