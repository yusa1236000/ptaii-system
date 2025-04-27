<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_prices', function (Blueprint $table) {
            $table->id('price_id');
            $table->unsignedBigInteger('item_id');
            $table->string('price_type', 50); // 'purchase' or 'sale'
            $table->decimal('price', 15, 4);
            $table->string('currency', 10)->default('USD');
            $table->decimal('min_quantity', 15, 4)->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable(); // For customer-specific pricing
            $table->unsignedBigInteger('vendor_id')->nullable(); // For vendor-specific pricing
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors');
        });

        // Add relevant fields to items table
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_purchasable')->default(true)->after('maximum_stock');
            $table->boolean('is_sellable')->default(true)->after('is_purchasable');
            $table->decimal('cost_price', 15, 4)->default(0)->after('is_sellable');
            $table->decimal('sale_price', 15, 4)->default(0)->after('cost_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('is_purchasable');
            $table->dropColumn('is_sellable');
            $table->dropColumn('cost_price');
            $table->dropColumn('sale_price');
        });
        
        Schema::dropIfExists('item_prices');
    }
}