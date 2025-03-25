<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id('rfq_id');
            $table->string('rfq_number', 50)->unique();
            $table->date('rfq_date');
            $table->date('validity_date')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_for_quotations');
    }
};