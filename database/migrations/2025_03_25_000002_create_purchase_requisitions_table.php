<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('purchase_requisitions')) {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id('pr_id');
            $table->string('pr_number', 50)->unique();
            $table->date('pr_date');
            $table->unsignedBigInteger('requester_id');
            $table->string('status', 50)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('requester_id')->references('id')->on('users');
        });
    }
    }

    public function down()
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};