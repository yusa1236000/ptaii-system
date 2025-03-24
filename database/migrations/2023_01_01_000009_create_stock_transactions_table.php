<?php
// database/migrations/2023_01_01_000009_create_stock_transactions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('transaction_type', 50);
            $table->float('quantity');
            $table->date('transaction_date');
            $table->string('reference_document', 100)->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamps();
            
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses');
            $table->foreign('location_id')->references('location_id')->on('warehouse_locations');
            $table->foreign('batch_id')->references('batch_id')->on('item_batches');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_transactions');
    }
};