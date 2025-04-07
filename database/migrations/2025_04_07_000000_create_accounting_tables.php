<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Chart of Accounts
        Schema::create('ChartOfAccount', function (Blueprint $table) {
            $table->id('account_id');
            $table->string('account_code', 50)->unique();
            $table->string('name', 100);
            $table->string('account_type', 50);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('parent_account_id')->nullable();
            $table->foreign('parent_account_id')->references('account_id')->on('ChartOfAccount');
        });

        // Accounting Periods
        Schema::create('AccountingPeriod', function (Blueprint $table) {
            $table->id('period_id');
            $table->string('period_name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 50);
        });

        // Journal Entries
        Schema::create('JournalEntry', function (Blueprint $table) {
            $table->id('journal_id');
            $table->string('journal_number', 50)->unique();
            $table->date('entry_date');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('period_id');
            $table->string('status', 50);
            $table->foreign('period_id')->references('period_id')->on('AccountingPeriod');
        });

        // Journal Entry Lines
        Schema::create('JournalEntryLine', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit_amount', 15, 2);
            $table->decimal('credit_amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreign('journal_id')->references('journal_id')->on('JournalEntry');
            $table->foreign('account_id')->references('account_id')->on('ChartOfAccount');
        });

        // Customer Receivables
        Schema::create('CustomerReceivable', function (Blueprint $table) {
            $table->id('receivable_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->string('status', 50);
            $table->foreign('customer_id')->references('customer_id')->on('Customer');
            $table->foreign('invoice_id')->references('invoice_id')->on('SalesInvoice');
        });

        // Receivable Payments
        Schema::create('ReceivablePayment', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('receivable_id');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50);
            $table->string('reference_number', 50);
            $table->foreign('receivable_id')->references('receivable_id')->on('CustomerReceivable');
        });

        // Vendor Payables
        Schema::create('VendorPayable', function (Blueprint $table) {
            $table->id('payable_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->string('status', 50);
            $table->foreign('vendor_id')->references('vendor_id')->on('Vendor');
            $table->foreign('invoice_id')->references('invoice_id')->on('VendorInvoice');
        });

        // Payable Payments
        Schema::create('PayablePayment', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('payable_id');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50);
            $table->string('reference_number', 50);
            $table->foreign('payable_id')->references('payable_id')->on('VendorPayable');
        });

        // Tax Transactions
        Schema::create('TaxTransaction', function (Blueprint $table) {
            $table->id('tax_id');
            $table->string('tax_type', 50);
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->date('transaction_date');
            $table->decimal('tax_amount', 15, 2);
            $table->string('tax_code', 50);
            $table->string('status', 50);
        });

        // Fixed Assets
        Schema::create('FixedAsset', function (Blueprint $table) {
            $table->id('asset_id');
            $table->string('asset_code', 50)->unique();
            $table->string('name', 100);
            $table->string('category', 50);
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 15, 2);
            $table->decimal('current_value', 15, 2);
            $table->decimal('depreciation_rate', 8, 4);
            $table->string('status', 50);
        });

        // Asset Depreciations
        Schema::create('AssetDepreciation', function (Blueprint $table) {
            $table->id('depreciation_id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('period_id');
            $table->date('depreciation_date');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2);
            $table->decimal('remaining_value', 15, 2);
            $table->foreign('asset_id')->references('asset_id')->on('FixedAsset');
            $table->foreign('period_id')->references('period_id')->on('AccountingPeriod');
        });

        // Budgets
        Schema::create('Budget', function (Blueprint $table) {
            $table->id('budget_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('period_id');
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->nullable();
            $table->decimal('variance', 15, 2)->nullable();
            $table->foreign('account_id')->references('account_id')->on('ChartOfAccount');
            $table->foreign('period_id')->references('period_id')->on('AccountingPeriod');
        });

        // Bank Accounts
        Schema::create('BankAccount', function (Blueprint $table) {
            $table->id('bank_id');
            $table->string('bank_name', 100);
            $table->string('account_number', 50);
            $table->string('account_name', 100);
            $table->decimal('current_balance', 15, 2);
            $table->unsignedBigInteger('gl_account_id');
            $table->foreign('gl_account_id')->references('account_id')->on('ChartOfAccount');
        });

        // Bank Reconciliations
        Schema::create('BankReconciliation', function (Blueprint $table) {
            $table->id('reconciliation_id');
            $table->unsignedBigInteger('bank_id');
            $table->date('statement_date');
            $table->decimal('statement_balance', 15, 2);
            $table->decimal('book_balance', 15, 2);
            $table->string('status', 50);
            $table->foreign('bank_id')->references('bank_id')->on('BankAccount');
        });

        // Bank Reconciliation Lines
        Schema::create('BankReconciliationLine', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('reconciliation_id');
            $table->string('transaction_type', 50);
            $table->unsignedBigInteger('transaction_id');
            $table->date('transaction_date');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_reconciled');
            $table->foreign('reconciliation_id')->references('reconciliation_id')->on('BankReconciliation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('BankReconciliationLine');
        Schema::dropIfExists('BankReconciliation');
        Schema::dropIfExists('BankAccount');
        Schema::dropIfExists('Budget');
        Schema::dropIfExists('AssetDepreciation');
        Schema::dropIfExists('FixedAsset');
        Schema::dropIfExists('TaxTransaction');
        Schema::dropIfExists('PayablePayment');
        Schema::dropIfExists('VendorPayable');
        Schema::dropIfExists('ReceivablePayment');
        Schema::dropIfExists('CustomerReceivable');
        Schema::dropIfExists('JournalEntryLine');
        Schema::dropIfExists('JournalEntry');
        Schema::dropIfExists('AccountingPeriod');
        Schema::dropIfExists('ChartOfAccount');
    }
}