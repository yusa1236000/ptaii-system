<?php
// database/migrations/2023_01_01_000003_create_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id('item_id');
            $table->string('item_code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->float('current_stock')->default(0);
            $table->float('minimum_stock')->default(0);
            $table->float('maximum_stock')->default(0);
            $table->timestamps();
            
            $table->foreign('category_id')->references('category_id')->on('item_categories');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });
    }

    public function down()
    {
        Schema::dropIfExists('items');
    }
};