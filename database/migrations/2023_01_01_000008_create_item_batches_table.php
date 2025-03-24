<?php
// database/migrations/2023_01_01_000008_create_item_batches_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('item_batches', function (Blueprint $table) {
            $table->id('batch_id');
            $table->unsignedBigInteger('item_id');
            $table->string('batch_number', 50);
            $table->date('expiry_date')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->string('lot_number', 50)->nullable();
            $table->timestamps();
            
            $table->foreign('item_id')->references('item_id')->on('items');
        });
    }

    public function down()
    {
        Schema::dropIfExists('item_batches');
    }
};