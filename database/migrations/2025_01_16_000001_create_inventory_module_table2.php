<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ItemCategory Table
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_category_id')->nullable();
            $table->timestamps();
        });

        // Add self-referencing foreign key after table creation
        Schema::table('item_categories', function (Blueprint $table) {
            $table->foreign('parent_category_id')
                  ->references('category_id')
                  ->on('item_categories')
                  ->onDelete('set null');
        });

        // UnitOfMeasure Table
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id('uom_id');
            $table->string('name', 50);
            $table->string('symbol', 10);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Item Table
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
            
            $table->foreign('category_id')
                  ->references('category_id')
                  ->on('item_categories')
                  ->onDelete('set null');
            $table->foreign('uom_id')
                  ->references('uom_id')
                  ->on('unit_of_measures')
                  ->onDelete('set null');
        });

        // UOMConversion Table
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id('conversion_id');
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->float('conversion_factor');
            $table->timestamps();
            
            $table->foreign('from_uom_id')
                  ->references('uom_id')
                  ->on('unit_of_measures')
                  ->onDelete('cascade');
            $table->foreign('to_uom_id')
                  ->references('uom_id')
                  ->on('unit_of_measures')
                  ->onDelete('cascade');
        });

        // Warehouse Table
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id('warehouse_id');
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // WarehouseZone Table
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id('zone_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name', 100);
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('warehouse_id')
                  ->references('warehouse_id')
                  ->on('warehouses')
                  ->onDelete('cascade');
        });

        // WarehouseLocation Table
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id('location_id');
            $table->unsignedBigInteger('zone_id');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('zone_id')
                  ->references('zone_id')
                  ->on('warehouse_zones')
                  ->onDelete('cascade');
        });

        // ItemBatch Table
        Schema::create('item_batches', function (Blueprint $table) {
            $table->id('batch_id');
            $table->unsignedBigInteger('item_id');
            $table->string('batch_number', 50);
            $table->date('expiry_date')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->string('lot_number', 50)->nullable();
            $table->timestamps();
            
            $table->foreign('item_id')
                  ->references('item_id')
                  ->on('items')
                  ->onDelete('cascade');
        });
        
        // StockTransaction Table
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('transaction_type', 50); // e.g., 'receive', 'issue', 'transfer', 'adjustment'
            $table->float('quantity');
            $table->date('transaction_date');
            $table->string('reference_document', 100)->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamps();
            
            $table->foreign('item_id')
                  ->references('item_id')
                  ->on('items')
                  ->onDelete('cascade');
            $table->foreign('warehouse_id')
                  ->references('warehouse_id')
                  ->on('warehouses')
                  ->onDelete('cascade');
            $table->foreign('location_id')
                  ->references('location_id')
                  ->on('warehouse_locations')
                  ->onDelete('set null');
            $table->foreign('batch_id')
                  ->references('batch_id')
                  ->on('item_batches')
                  ->onDelete('set null');
        });

        // Create users table for foreign key reference if it doesn't exist
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Create vendors table for foreign key reference
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

        // ConsignmentStock Table
        Schema::create('consignment_stocks', function (Blueprint $table) {
            $table->id('consignment_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->float('quantity');
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            
            $table->foreign('item_id')
                  ->references('item_id')
                  ->on('items')
                  ->onDelete('cascade');
            $table->foreign('vendor_id')
                  ->references('vendor_id')
                  ->on('vendors')
                  ->onDelete('cascade');
            $table->foreign('warehouse_id')
                  ->references('warehouse_id')
                  ->on('warehouses')
                  ->onDelete('cascade');
        });

        // CycleCounting Table
        Schema::create('cycle_countings', function (Blueprint $table) {
            $table->id('count_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->float('book_quantity');
            $table->float('actual_quantity');
            $table->float('variance');
            $table->date('count_date');
            $table->string('status', 50);
            $table->timestamps();
            
            $table->foreign('item_id')
                  ->references('item_id')
                  ->on('items')
                  ->onDelete('cascade');
            $table->foreign('warehouse_id')
                  ->references('warehouse_id')
                  ->on('warehouses')
                  ->onDelete('cascade');
            $table->foreign('location_id')
                  ->references('location_id')
                  ->on('warehouse_locations')
                  ->onDelete('set null');
        });

        // StockAdjustment Table
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id('adjustment_id');
            $table->date('adjustment_date');
            $table->text('adjustment_reason')->nullable();
            $table->string('status', 50);
            $table->string('reference_document', 100)->nullable();
            $table->timestamps();
        });

        // StockAdjustmentLine Table
        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('adjustment_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->float('book_quantity');
            $table->float('adjusted_quantity');
            $table->float('variance');
            $table->timestamps();
            
            $table->foreign('adjustment_id')
                  ->references('adjustment_id')
                  ->on('stock_adjustments')
                  ->onDelete('cascade');
            $table->foreign('item_id')
                  ->references('item_id')
                  ->on('items')
                  ->onDelete('cascade');
            $table->foreign('warehouse_id')
                  ->references('warehouse_id')
                  ->on('warehouses')
                  ->onDelete('cascade');
            $table->foreign('location_id')
                  ->references('location_id')
                  ->on('warehouse_locations')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('cycle_countings');
        Schema::dropIfExists('consignment_stocks');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('item_batches');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouse_zones');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('items');
        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('item_categories');
    }
};