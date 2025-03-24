<?php

// database/migrations/2023_01_01_000001_create_item_categories_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_category_id')->nullable();
            $table->timestamps();
            
            $table->foreign('parent_category_id')->references('category_id')->on('item_categories');
        });
    }

    public function down()
    {
        Schema::dropIfExists('item_categories');
    }
};