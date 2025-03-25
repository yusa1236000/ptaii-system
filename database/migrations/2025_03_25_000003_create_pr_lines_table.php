<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pr_lines', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('pr_id');
            $table->unsignedBigInteger('item_id');
            $table->float('quantity');
            $table->unsignedBigInteger('uom_id');
            $table->date('required_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('pr_id')->references('pr_id')->on('purchase_requisitions')->onDelete('cascade');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pr_lines');
    }
};