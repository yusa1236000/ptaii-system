<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id('vendor_id');
            $table->string('vendor_code', 50)->unique();
            $table->string('name', 100);
            $table->text('address')->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendors');
    }
};