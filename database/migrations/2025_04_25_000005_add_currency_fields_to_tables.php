<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyFieldsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create currency_rates table
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id('rate_id');
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 15, 6);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add unique constraint for currency pairs and effective dates
            $table->unique(['from_currency', 'to_currency', 'effective_date']);
        });

        // Modify purchase_orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('USD')->after('tax_amount');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('currency_code');
            $table->string('base_currency', 3)->default('USD')->after('exchange_rate');
            $table->decimal('base_currency_total', 15, 2)->default(0)->after('base_currency');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('base_currency_total');
        });
        
        // Modify po_lines table
        Schema::table('po_lines', function (Blueprint $table) {
            $table->decimal('base_currency_unit_price', 15, 4)->default(0)->after('total');
            $table->decimal('base_currency_subtotal', 15, 2)->default(0)->after('base_currency_unit_price');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('base_currency_subtotal');
            $table->decimal('base_currency_total', 15, 2)->default(0)->after('base_currency_tax');
        });

        // Modify SalesOrder table
        Schema::table('SalesOrder', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('USD')->after('tax_amount');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('currency_code');
            $table->string('base_currency', 3)->default('USD')->after('exchange_rate');
            $table->decimal('base_currency_total', 15, 2)->default(0)->after('base_currency');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('base_currency_total');
        });
        
        // Modify SOLine table
        Schema::table('SOLine', function (Blueprint $table) {
            $table->decimal('base_currency_unit_price', 15, 4)->default(0)->after('total');
            $table->decimal('base_currency_subtotal', 15, 2)->default(0)->after('base_currency_unit_price');
            $table->decimal('base_currency_discount', 15, 2)->default(0)->after('base_currency_subtotal');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('base_currency_discount');
            $table->decimal('base_currency_total', 15, 2)->default(0)->after('base_currency_tax');
        });

        // Modify vendor_invoices table
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('USD')->after('status');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('currency_code');
            $table->string('base_currency', 3)->default('USD')->after('exchange_rate');
            $table->decimal('base_currency_total', 15, 2)->default(0)->after('base_currency');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('base_currency_total');
        });
        
        // Modify vendor_invoice_lines table
        Schema::table('vendor_invoice_lines', function (Blueprint $table) {
            $table->decimal('base_currency_unit_price', 15, 4)->default(0)->after('total');
            $table->decimal('base_currency_subtotal', 15, 2)->default(0)->after('base_currency_unit_price');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('base_currency_subtotal');
            $table->decimal('base_currency_total', 15, 2)->default(0)->after('base_currency_tax');
        });

        // Modify SalesInvoice table
        Schema::table('SalesInvoice', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('USD')->after('tax_amount');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('currency_code');
            $table->string('base_currency', 3)->default('USD')->after('exchange_rate');
            $table->decimal('base_currency_total', 15, 2)->default(0)->after('base_currency');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('base_currency_total');
        });
        
        // Modify SalesInvoiceLine table
        Schema::table('SalesInvoiceLine', function (Blueprint $table) {
            $table->decimal('base_currency_unit_price', 15, 4)->default(0)->after('total');
            $table->decimal('base_currency_subtotal', 15, 2)->default(0)->after('base_currency_unit_price');
            $table->decimal('base_currency_discount', 15, 2)->default(0)->after('base_currency_subtotal');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('base_currency_discount');
            $table->decimal('base_currency_total', 15, 2)->default(0)->after('base_currency_tax');
        });

        // Modify VendorPayable table
        Schema::table('VendorPayable', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('USD')->after('status');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('currency_code');
            $table->string('base_currency', 3)->default('USD')->after('exchange_rate');
            $table->decimal('base_currency_amount', 15, 2)->default(0)->after('base_currency');
            $table->decimal('base_currency_balance', 15, 2)->default(0)->after('base_currency_amount');
        });

        // Modify CustomerReceivable table
        Schema::table('CustomerReceivable', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('USD')->after('status');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('currency_code');
            $table->string('base_currency', 3)->default('USD')->after('exchange_rate');
            $table->decimal('base_currency_amount', 15, 2)->default(0)->after('base_currency');
            $table->decimal('base_currency_balance', 15, 2)->default(0)->after('base_currency_amount');
        });

        // Modify PayablePayment table
        Schema::table('PayablePayment', function (Blueprint $table) {
            $table->string('payment_currency', 3)->default('USD')->after('reference_number');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('payment_currency');
            $table->decimal('payable_amount', 15, 2)->default(0)->after('exchange_rate');
            $table->decimal('exchange_difference', 15, 2)->default(0)->after('payable_amount');
        });

        // Modify ReceivablePayment table
        Schema::table('ReceivablePayment', function (Blueprint $table) {
            $table->string('payment_currency', 3)->default('USD')->after('reference_number');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('payment_currency');
            $table->decimal('receivable_amount', 15, 2)->default(0)->after('exchange_rate');
            $table->decimal('exchange_difference', 15, 2)->default(0)->after('receivable_amount');
        });

        // Modify item_prices table for multi-currency support
        Schema::table('item_prices', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('USD')->after('price');
            $table->decimal('base_currency_price', 15, 4)->default(0)->after('currency_code');
            $table->string('base_currency', 3)->default('USD')->after('base_currency_price');
        });

        // Add preferred_currency to Customer table
        Schema::table('Customer', function (Blueprint $table) {
            $table->string('preferred_currency', 3)->default('USD')->after('email');
        });

        // Add preferred_currency to Vendor table
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('preferred_currency', 3)->default('USD')->after('email');
        });

        // Add to Items table
        Schema::table('items', function (Blueprint $table) {
            $table->string('cost_price_currency', 10)->default('USD')->after('cost_price');
            $table->string('sale_price_currency', 10)->default('USD')->after('sale_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the currency_rates table
        Schema::dropIfExists('currency_rates');

        // Remove fields from purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('currency_code');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('base_currency');
            $table->dropColumn('base_currency_total');
            $table->dropColumn('base_currency_tax');
        });
        
        // Remove fields from po_lines
        Schema::table('po_lines', function (Blueprint $table) {
            $table->dropColumn('base_currency_unit_price');
            $table->dropColumn('base_currency_subtotal');
            $table->dropColumn('base_currency_tax');
            $table->dropColumn('base_currency_total');
        });

        // Remove fields from SalesOrder
        Schema::table('SalesOrder', function (Blueprint $table) {
            $table->dropColumn('currency_code');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('base_currency');
            $table->dropColumn('base_currency_total');
            $table->dropColumn('base_currency_tax');
        });
        
        // Remove fields from SOLine
        Schema::table('SOLine', function (Blueprint $table) {
            $table->dropColumn('base_currency_unit_price');
            $table->dropColumn('base_currency_subtotal');
            $table->dropColumn('base_currency_discount');
            $table->dropColumn('base_currency_tax');
            $table->dropColumn('base_currency_total');
        });

        // Remove fields from vendor_invoices
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->dropColumn('currency_code');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('base_currency');
            $table->dropColumn('base_currency_total');
            $table->dropColumn('base_currency_tax');
        });
        
        // Remove fields from vendor_invoice_lines
        Schema::table('vendor_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('base_currency_unit_price');
            $table->dropColumn('base_currency_subtotal');
            $table->dropColumn('base_currency_tax');
            $table->dropColumn('base_currency_total');
        });

        // Remove fields from SalesInvoice
        Schema::table('SalesInvoice', function (Blueprint $table) {
            $table->dropColumn('currency_code');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('base_currency');
            $table->dropColumn('base_currency_total');
            $table->dropColumn('base_currency_tax');
        });
        
        // Remove fields from SalesInvoiceLine
        Schema::table('SalesInvoiceLine', function (Blueprint $table) {
            $table->dropColumn('base_currency_unit_price');
            $table->dropColumn('base_currency_subtotal');
            $table->dropColumn('base_currency_discount');
            $table->dropColumn('base_currency_tax');
            $table->dropColumn('base_currency_total');
        });

        // Remove fields from VendorPayable
        Schema::table('VendorPayable', function (Blueprint $table) {
            $table->dropColumn('currency_code');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('base_currency');
            $table->dropColumn('base_currency_amount');
            $table->dropColumn('base_currency_balance');
        });

        // Remove fields from CustomerReceivable
        Schema::table('CustomerReceivable', function (Blueprint $table) {
            $table->dropColumn('currency_code');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('base_currency');
            $table->dropColumn('base_currency_amount');
            $table->dropColumn('base_currency_balance');
        });

        // Remove fields from PayablePayment
        Schema::table('PayablePayment', function (Blueprint $table) {
            $table->dropColumn('payment_currency');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('payable_amount');
            $table->dropColumn('exchange_difference');
        });

        // Remove fields from ReceivablePayment
        Schema::table('ReceivablePayment', function (Blueprint $table) {
            $table->dropColumn('payment_currency');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('receivable_amount');
            $table->dropColumn('exchange_difference');
        });

        // Remove fields from item_prices
        Schema::table('item_prices', function (Blueprint $table) {
            $table->dropColumn('currency_code');
            $table->dropColumn('base_currency_price');
            $table->dropColumn('base_currency');
        });

        // Remove preferred_currency from Customer
        Schema::table('Customer', function (Blueprint $table) {
            $table->dropColumn('preferred_currency');
        });

        // Remove preferred_currency from vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('preferred_currency');
        });
    }
}