<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Sales\CustomerController;
use App\Http\Controllers\Api\Sales\SalesQuotationController;
use App\Http\Controllers\Api\Sales\SalesOrderController;
use App\Http\Controllers\Api\Sales\DeliveryController;
use App\Http\Controllers\Api\Sales\SalesInvoiceController;
use App\Http\Controllers\Api\Sales\SalesReturnController;
use App\Http\Controllers\Api\Sales\CustomerInteractionController;
use App\Http\Controllers\Api\Sales\SalesCommissionController;
use App\Http\Controllers\Api\Sales\SalesForecastController;

/*
|--------------------------------------------------------------------------
| Sales Module Routes
|--------------------------------------------------------------------------
|
| This file contains all the routes for the Sales Module.
|
*/

Route::middleware(['web', 'auth'])->prefix('sales')->group(function () {
    // Dashboard
    Route::get('/', function () {
        return view('sales.dashboard');
    })->name('sales.dashboard');

    // Customer routes
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('sales.customers.index');
        Route::get('/create', [CustomerController::class, 'create'])->name('sales.customers.create');
        Route::post('/', [CustomerController::class, 'store'])->name('sales.customers.store');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('sales.customers.show');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('sales.customers.edit');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('sales.customers.update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('sales.customers.destroy');
    });

    // Sales Quotation routes
    Route::prefix('quotations')->group(function () {
        Route::get('/', [SalesQuotationController::class, 'index'])->name('sales.quotations.index');
        Route::get('/create', [SalesQuotationController::class, 'create'])->name('sales.quotations.create');
        Route::post('/', [SalesQuotationController::class, 'store'])->name('sales.quotations.store');
        Route::get('/{id}', [SalesQuotationController::class, 'show'])->name('sales.quotations.show');
        Route::get('/{id}/edit', [SalesQuotationController::class, 'edit'])->name('sales.quotations.edit');
        Route::put('/{id}', [SalesQuotationController::class, 'update'])->name('sales.quotations.update');
        Route::delete('/{id}', [SalesQuotationController::class, 'destroy'])->name('sales.quotations.destroy');
    });

    // Sales Order routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [SalesOrderController::class, 'index'])->name('sales.orders.index');
        Route::get('/create', [SalesOrderController::class, 'create'])->name('sales.orders.create');
        Route::post('/', [SalesOrderController::class, 'store'])->name('sales.orders.store');
        Route::get('/create-from-quotation/{quotationId}', [SalesOrderController::class, 'createFromQuotation'])->name('sales.orders.createFromQuotation');
        Route::post('/from-quotation', [SalesOrderController::class, 'storeFromQuotation'])->name('sales.orders.storeFromQuotation');
        Route::get('/{id}', [SalesOrderController::class, 'show'])->name('sales.orders.show');
        Route::get('/{id}/edit', [SalesOrderController::class, 'edit'])->name('sales.orders.edit');
        Route::put('/{id}', [SalesOrderController::class, 'update'])->name('sales.orders.update');
        Route::delete('/{id}', [SalesOrderController::class, 'destroy'])->name('sales.orders.destroy');
    });

    // Delivery routes
    Route::prefix('deliveries')->group(function () {
        Route::get('/', [DeliveryController::class, 'index'])->name('sales.deliveries.index');
        Route::get('/create', [DeliveryController::class, 'create'])->name('sales.deliveries.create');
        Route::post('/', [DeliveryController::class, 'store'])->name('sales.deliveries.store');
        Route::get('/create-from-order/{orderId}', [DeliveryController::class, 'createFromOrder'])->name('sales.deliveries.createFromOrder');
        Route::get('/{id}', [DeliveryController::class, 'show'])->name('sales.deliveries.show');
        Route::get('/{id}/edit', [DeliveryController::class, 'edit'])->name('sales.deliveries.edit');
        Route::put('/{id}', [DeliveryController::class, 'update'])->name('sales.deliveries.update');
        Route::delete('/{id}', [DeliveryController::class, 'destroy'])->name('sales.deliveries.destroy');
        Route::post('/{id}/complete', [DeliveryController::class, 'complete'])->name('sales.deliveries.complete');
    });

    // Sales Invoice routes
    Route::prefix('invoices')->group(function () {
        Route::get('/', [SalesInvoiceController::class, 'index'])->name('sales.invoices.index');
        Route::get('/create', [SalesInvoiceController::class, 'create'])->name('sales.invoices.create');
        Route::post('/', [SalesInvoiceController::class, 'store'])->name('sales.invoices.store');
        Route::get('/create-from-order/{orderId}', [SalesInvoiceController::class, 'createFromOrder'])->name('sales.invoices.createFromOrder');
        Route::post('/from-order', [SalesInvoiceController::class, 'storeFromOrder'])->name('sales.invoices.storeFromOrder');
        Route::get('/{id}', [SalesInvoiceController::class, 'show'])->name('sales.invoices.show');
        Route::get('/{id}/edit', [SalesInvoiceController::class, 'edit'])->name('sales.invoices.edit');
        Route::put('/{id}', [SalesInvoiceController::class, 'update'])->name('sales.invoices.update');
        Route::delete('/{id}', [SalesInvoiceController::class, 'destroy'])->name('sales.invoices.destroy');
        Route::get('/{id}/print', [SalesInvoiceController::class, 'print'])->name('sales.invoices.print');
    });

    // Sales Return routes
    Route::prefix('returns')->group(function () {
        Route::get('/', [SalesReturnController::class, 'index'])->name('sales.returns.index');
        Route::get('/create', [SalesReturnController::class, 'create'])->name('sales.returns.create');
        Route::post('/', [SalesReturnController::class, 'store'])->name('sales.returns.store');
        Route::get('/create-from-invoice/{invoiceId}', [SalesReturnController::class, 'createFromInvoice'])->name('sales.returns.createFromInvoice');
        Route::get('/{id}', [SalesReturnController::class, 'show'])->name('sales.returns.show');
        Route::get('/{id}/edit', [SalesReturnController::class, 'edit'])->name('sales.returns.edit');
        Route::put('/{id}', [SalesReturnController::class, 'update'])->name('sales.returns.update');
        Route::delete('/{id}', [SalesReturnController::class, 'destroy'])->name('sales.returns.destroy');
        Route::post('/{id}/process', [SalesReturnController::class, 'process'])->name('sales.returns.process');
    });

    // Customer Interaction routes
    Route::prefix('interactions')->group(function () {
        Route::get('/', [CustomerInteractionController::class, 'index'])->name('sales.interactions.index');
        Route::get('/create', [CustomerInteractionController::class, 'create'])->name('sales.interactions.create');
        Route::post('/', [CustomerInteractionController::class, 'store'])->name('sales.interactions.store');
        Route::get('/{id}', [CustomerInteractionController::class, 'show'])->name('sales.interactions.show');
        Route::get('/{id}/edit', [CustomerInteractionController::class, 'edit'])->name('sales.interactions.edit');
        Route::put('/{id}', [CustomerInteractionController::class, 'update'])->name('sales.interactions.update');
        Route::delete('/{id}', [CustomerInteractionController::class, 'destroy'])->name('sales.interactions.destroy');
        Route::get('/customer/{customerId}', [CustomerInteractionController::class, 'getCustomerInteractions'])->name('sales.interactions.customer');
    });

    // Sales Commission routes
    Route::prefix('commissions')->group(function () {
        Route::get('/', [SalesCommissionController::class, 'index'])->name('sales.commissions.index');
        Route::get('/create', [SalesCommissionController::class, 'create'])->name('sales.commissions.create');
        Route::post('/', [SalesCommissionController::class, 'store'])->name('sales.commissions.store');
        Route::get('/calculate', [SalesCommissionController::class, 'calculateForm'])->name('sales.commissions.calculateForm');
        Route::post('/calculate', [SalesCommissionController::class, 'calculate'])->name('sales.commissions.calculate');
        Route::get('/{id}', [SalesCommissionController::class, 'show'])->name('sales.commissions.show');
        Route::get('/{id}/edit', [SalesCommissionController::class, 'edit'])->name('sales.commissions.edit');
        Route::put('/{id}', [SalesCommissionController::class, 'update'])->name('sales.commissions.update');
        Route::delete('/{id}', [SalesCommissionController::class, 'destroy'])->name('sales.commissions.destroy');
        Route::get('/sales-person/{salesPersonId}', [SalesCommissionController::class, 'getSalesPersonCommissions'])->name('sales.commissions.salesPerson');
        Route::post('/mark-as-paid', [SalesCommissionController::class, 'markAsPaid'])->name('sales.commissions.markAsPaid');
    });

    // Sales Forecast routes
    Route::prefix('forecasts')->group(function () {
        Route::get('/', [SalesForecastController::class, 'index'])->name('sales.forecasts.index');
        Route::get('/create', [SalesForecastController::class, 'create'])->name('sales.forecasts.create');
        Route::post('/', [SalesForecastController::class, 'store'])->name('sales.forecasts.store');
        Route::get('/generate', [SalesForecastController::class, 'generateForm'])->name('sales.forecasts.generateForm');
        Route::post('/generate', [SalesForecastController::class, 'generate'])->name('sales.forecasts.generate');
        Route::get('/update-actuals', [SalesForecastController::class, 'updateActualsForm'])->name('sales.forecasts.updateActualsForm');
        Route::post('/update-actuals', [SalesForecastController::class, 'updateActuals'])->name('sales.forecasts.updateActuals');
        Route::get('/accuracy', [SalesForecastController::class, 'accuracyForm'])->name('sales.forecasts.accuracyForm');
        Route::post('/accuracy', [SalesForecastController::class, 'accuracy'])->name('sales.forecasts.accuracy');
        Route::get('/{id}', [SalesForecastController::class, 'show'])->name('sales.forecasts.show');
        Route::get('/{id}/edit', [SalesForecastController::class, 'edit'])->name('sales.forecasts.edit');
        Route::put('/{id}', [SalesForecastController::class, 'update'])->name('sales.forecasts.update');
        Route::delete('/{id}', [SalesForecastController::class, 'destroy'])->name('sales.forecasts.destroy');
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/sales-by-customer', [SalesReportController::class, 'salesByCustomer'])->name('sales.reports.salesByCustomer');
        Route::get('/sales-by-product', [SalesReportController::class, 'salesByProduct'])->name('sales.reports.salesByProduct');
        Route::get('/sales-by-period', [SalesReportController::class, 'salesByPeriod'])->name('sales.reports.salesByPeriod');
        Route::get('/quotation-conversion', [SalesReportController::class, 'quotationConversion'])->name('sales.reports.quotationConversion');
        Route::get('/sales-person-performance', [SalesReportController::class, 'salesPersonPerformance'])->name('sales.reports.salesPersonPerformance');
    });
});