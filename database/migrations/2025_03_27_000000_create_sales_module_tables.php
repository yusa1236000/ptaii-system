<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesModuleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * Master Data Tables
         */

        // Create Customer table
        Schema::create('Customer', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('customer_code', 50)->unique();
            $table->string('name', 100);
            $table->text('address')->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('status', 50);
        });

        /**
         * Sales Quotation Tables
         */

        // Create SalesQuotation table
        Schema::create('SalesQuotation', function (Blueprint $table) {
            $table->id('quotation_id');
            $table->string('quotation_number', 50)->unique();
            $table->date('quotation_date');
            $table->unsignedBigInteger('customer_id');
            $table->date('validity_date');
            $table->string('status', 50);
            $table->text('payment_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
        });

        // Create SalesQuotationLine table
        Schema::create('SalesQuotationLine', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('item_id');
            $table->float('unit_price');
            $table->float('quantity');
            $table->unsignedBigInteger('uom_id');
            $table->float('discount')->default(0);
            $table->float('subtotal');
            $table->float('tax')->default(0);
            $table->float('total');
            
            $table->foreign('quotation_id')->references('quotation_id')->on('SalesQuotation')->onDelete('cascade');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });

        /**
         * Sales Order Tables
         */

        // Create SalesOrder table
        Schema::create('SalesOrder', function (Blueprint $table) {
            $table->id('so_id');
            $table->string('so_number', 50)->unique();
            $table->date('so_date');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->date('expected_delivery')->nullable();
            $table->string('status', 50);
            $table->float('total_amount');
            $table->float('tax_amount')->default(0);
            
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
            $table->foreign('quotation_id')->references('quotation_id')->on('SalesQuotation');
        });

        // Create SOLine table
        Schema::create('SOLine', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('so_id');
            $table->unsignedBigInteger('item_id');
            $table->float('unit_price');
            $table->float('quantity');
            $table->unsignedBigInteger('uom_id');
            $table->float('discount')->default(0);
            $table->float('subtotal');
            $table->float('tax')->default(0);
            $table->float('total');
            
            $table->foreign('so_id')->references('so_id')->on('SalesOrder')->onDelete('cascade');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('uom_id')->references('uom_id')->on('unit_of_measures');
        });

        /**
         * Delivery Tables
         */

        // Create Delivery table
        Schema::create('Delivery', function (Blueprint $table) {
            $table->id('delivery_id');
            $table->string('delivery_number', 50)->unique();
            $table->date('delivery_date');
            $table->unsignedBigInteger('so_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('status', 50);
            $table->string('shipping_method', 50)->nullable();
            $table->string('tracking_number', 50)->nullable();
            
            $table->foreign('so_id')->references('so_id')->on('SalesOrder');
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
        });

        // Create DeliveryLine table
        Schema::create('DeliveryLine', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('delivery_id');
            $table->unsignedBigInteger('so_line_id');
            $table->unsignedBigInteger('item_id');
            $table->float('delivered_quantity');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id');
            $table->string('batch_number', 50)->nullable();
            
            $table->foreign('delivery_id')->references('delivery_id')->on('Delivery')->onDelete('cascade');
            $table->foreign('so_line_id')->references('line_id')->on('SOLine');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('Warehouses');
            $table->foreign('location_id')->references('location_id')->on('warehouse_locations');
        });

        /**
         * Invoice Tables
         */

        // Create SalesInvoice table
        Schema::create('SalesInvoice', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->string('invoice_number', 50)->unique();
            $table->date('invoice_date');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('so_id');
            $table->float('total_amount');
            $table->float('tax_amount')->default(0);
            $table->date('due_date');
            $table->string('status', 50);
            
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
            $table->foreign('so_id')->references('so_id')->on('SalesOrder');
        });

        // Create SalesInvoiceLine table
        Schema::create('SalesInvoiceLine', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('so_line_id');
            $table->unsignedBigInteger('item_id');
            $table->float('quantity');
            $table->float('unit_price');
            $table->float('discount')->default(0);
            $table->float('subtotal');
            $table->float('tax')->default(0);
            $table->float('total');
            
            $table->foreign('invoice_id')->references('invoice_id')->on('SalesInvoice')->onDelete('cascade');
            $table->foreign('so_line_id')->references('line_id')->on('SOLine');
            $table->foreign('item_id')->references('item_id')->on('items');
        });

        /**
         * Return Tables
         */

        // Create SalesReturn table
        Schema::create('SalesReturn', function (Blueprint $table) {
            $table->id('return_id');
            $table->string('return_number', 50)->unique();
            $table->date('return_date');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('invoice_id');
            $table->text('return_reason');
            $table->string('status', 50);
            
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
            $table->foreign('invoice_id')->references('invoice_id')->on('SalesInvoice');
        });

        // Create SalesReturnLine table
        Schema::create('SalesReturnLine', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('return_id');
            $table->unsignedBigInteger('invoice_line_id');
            $table->unsignedBigInteger('item_id');
            $table->float('returned_quantity');
            $table->string('condition', 50);
            
            $table->foreign('return_id')->references('return_id')->on('SalesReturn')->onDelete('cascade');
            $table->foreign('invoice_line_id')->references('line_id')->on('SalesInvoiceLine');
            $table->foreign('item_id')->references('item_id')->on('items');
        });

        /**
         * Customer Interaction Tables
         */

        // Create CustomerInteraction table
        Schema::create('CustomerInteraction', function (Blueprint $table) {
            $table->id('interaction_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('interaction_date');
            $table->string('interaction_type', 50);
            $table->text('notes');
            $table->unsignedBigInteger('user_id');
            
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
            $table->foreign('user_id')->references('id')->on('Users');
        });

        /**
         * Sales Commission Tables
         */

        // Create SalesCommission table
        Schema::create('SalesCommission', function (Blueprint $table) {
            $table->id('commission_id');
            $table->unsignedBigInteger('sales_person_id');
            $table->unsignedBigInteger('invoice_id');
            $table->float('commission_amount');
            $table->date('calculation_date');
            $table->string('status', 50);
            
            $table->foreign('sales_person_id')->references('id')->on('Users');
            $table->foreign('invoice_id')->references('invoice_id')->on('SalesInvoice');
        });

        /**
         * Sales Forecast Tables
         */

        // Create SalesForecast table
        Schema::create('SalesForecast', function (Blueprint $table) {
            $table->id('forecast_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('forecast_period');
            $table->float('forecast_quantity');
            $table->float('actual_quantity')->nullable();
            $table->float('variance')->nullable();
            
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
        });

        /**
         * Add timestamps to all tables if needed
         */
         
        // Uncomment this section if you need timestamps on all tables
        
        Schema::table('Customer', function (Blueprint $table) {
            $table->timestamps();
        });
        
        Schema::table('SalesQuotation', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('SalesOrder', function (Blueprint $table) {
            $table->timestamps();
        });
        
        // ... Add to other tables as needed
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop tables in reverse order of creation (to respect foreign key constraints)
        Schema::dropIfExists('SalesForecast');
        Schema::dropIfExists('SalesCommission');
        Schema::dropIfExists('CustomerInteraction');
        Schema::dropIfExists('SalesReturnLine');
        Schema::dropIfExists('SalesReturn');
        Schema::dropIfExists('SalesInvoiceLine');
        Schema::dropIfExists('SalesInvoice');
        Schema::dropIfExists('DeliveryLine');
        Schema::dropIfExists('Delivery');
        Schema::dropIfExists('SOLine');
        Schema::dropIfExists('SalesOrder');
        Schema::dropIfExists('SalesQuotationLine');
        Schema::dropIfExists('SalesQuotation');
        Schema::dropIfExists('Customer');
    }
}