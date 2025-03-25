<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// routes/api.php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemCategoryController;
use App\Http\Controllers\Api\UnitOfMeasureController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\StockTransactionController;
use App\Http\Controllers\Api\StockAdjustmentController;
// purchase order
use App\Http\Controllers\API\VendorController;
use App\Http\Controllers\API\PurchaseRequisitionController;
use App\Http\Controllers\API\RequestForQuotationController;
use App\Http\Controllers\API\VendorQuotationController;
use App\Http\Controllers\API\PurchaseOrderController;
use App\Http\Controllers\API\GoodsReceiptController;
use App\Http\Controllers\API\VendorInvoiceController;
use App\Http\Controllers\API\VendorContractController;
use App\Http\Controllers\API\VendorEvaluationController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Item Routes
    Route::apiResource('items', ItemController::class);
    Route::get('/items/stock-status', [ItemController::class, 'stockStatus']);
    
    // Category Routes
    Route::apiResource('item-categories', CategoryController::class);
    
    // UOM Routes
    Route::apiResource('unit-of-measures', UnitOfMeasureController::class);
    
    // Warehouse Routes
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('warehouses.zones', WarehouseZoneController::class);
    Route::apiResource('warehouses.zones.locations', WarehouseLocationController::class);
    
    // Transaction Routes
    Route::apiResource('stock-transactions', StockTransactionController::class);
    
    // Adjustment Routes
    Route::apiResource('stock-adjustments', StockAdjustmentController::class);
    Route::patch('/stock-adjustments/{stock_adjustment}/approve', [StockAdjustmentController::class, 'approve']);
    Route::patch('/stock-adjustments/{stock_adjustment}/cancel', [StockAdjustmentController::class, 'cancel']);
    
    // Reports
    Route::get('/reports/stock', [ReportController::class, 'stockReport']);
    Route::get('/reports/movement', [ReportController::class, 'movementReport']);
    Route::get('/reports/adjustment', [ReportController::class, 'adjustmentReport']);
    Route::get('/reports/valuation', [ReportController::class, 'valuationReport']);

    // Vendors
    Route::apiResource('vendors', VendorController::class);
    Route::get('vendors/{vendor}/evaluations', [VendorController::class, 'evaluations']);
    Route::get('vendors/{vendor}/purchase-orders', [VendorController::class, 'purchaseOrders']);
    
    // Purchase Requisitions
    Route::apiResource('purchase-requisitions', PurchaseRequisitionController::class);
    Route::patch('purchase-requisitions/{purchaseRequisition}/status', [PurchaseRequisitionController::class, 'updateStatus']);
    
    // Request For Quotations
    Route::apiResource('request-for-quotations', RequestForQuotationController::class);
    Route::patch('request-for-quotations/{requestForQuotation}/status', [RequestForQuotationController::class, 'updateStatus']);
    
    // Vendor Quotations
    Route::apiResource('vendor-quotations', VendorQuotationController::class);
    Route::patch('vendor-quotations/{vendorQuotation}/status', [VendorQuotationController::class, 'updateStatus']);
    
    // Purchase Orders
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::patch('purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus']);
    Route::post('purchase-orders/create-from-quotation', [PurchaseOrderController::class, 'createFromQuotation']);
    
    // Goods Receipts
    Route::apiResource('goods-receipts', GoodsReceiptController::class);
    Route::post('goods-receipts/{goodsReceipt}/confirm', [GoodsReceiptController::class, 'confirm']);
    
    // Vendor Invoices
    Route::apiResource('vendor-invoices', VendorInvoiceController::class);
    Route::post('vendor-invoices/{vendorInvoice}/approve', [VendorInvoiceController::class, 'approve']);
    Route::post('vendor-invoices/{vendorInvoice}/pay', [VendorInvoiceController::class, 'pay']);
    
    // Vendor Contracts
    Route::apiResource('vendor-contracts', VendorContractController::class);
    Route::post('vendor-contracts/{vendorContract}/activate', [VendorContractController::class, 'activate']);
    Route::post('vendor-contracts/{vendorContract}/terminate', [VendorContractController::class, 'terminate']);
    
    // Vendor Evaluations
    Route::apiResource('vendor-evaluations', VendorEvaluationController::class);
    Route::get('vendor-performance', [VendorEvaluationController::class, 'vendorPerformance']);
});

Route::middleware('api')->group(function () {
    // Item Category Routes
    Route::prefix('item-categories')->group(function () {
        Route::get('/', [ItemCategoryController::class, 'index']);
        Route::post('/', [ItemCategoryController::class, 'store']);
        Route::get('/{id}', [ItemCategoryController::class, 'show']);
        Route::put('/{id}', [ItemCategoryController::class, 'update']);
        Route::delete('/{id}', [ItemCategoryController::class, 'destroy']);
    });

    // Unit of Measure Routes
    Route::prefix('unit-of-measures')->group(function () {
        Route::get('/', [UnitOfMeasureController::class, 'index']);
        Route::post('/', [UnitOfMeasureController::class, 'store']);
        Route::get('/{id}', [UnitOfMeasureController::class, 'show']);
        Route::put('/{id}', [UnitOfMeasureController::class, 'update']);
        Route::delete('/{id}', [UnitOfMeasureController::class, 'destroy']);
    });

    // Item Routes
    Route::prefix('items')->group(function () {
        Route::get('/', [ItemController::class, 'index']);
        Route::post('/', [ItemController::class, 'store']);
        Route::get('/stock-status', [ItemController::class, 'getStockStatus']);
        Route::get('/{id}', [ItemController::class, 'show']);
        Route::put('/{id}', [ItemController::class, 'update']);
        Route::delete('/{id}', [ItemController::class, 'destroy']);
    });

    // Warehouse Routes
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [WarehouseController::class, 'index']);
        Route::post('/', [WarehouseController::class, 'store']);
        Route::get('/{id}', [WarehouseController::class, 'show']);
        Route::put('/{id}', [WarehouseController::class, 'update']);
        Route::delete('/{id}', [WarehouseController::class, 'destroy']);
    });

    // Stock Transaction Routes
    Route::prefix('stock-transactions')->group(function () {
        Route::get('/', [StockTransactionController::class, 'index']);
        Route::post('/', [StockTransactionController::class, 'store']);
        Route::get('/{id}', [StockTransactionController::class, 'show']);
        Route::get('/item/{itemId}', [StockTransactionController::class, 'getItemTransactions']);
        Route::get('/warehouse/{warehouseId}', [StockTransactionController::class, 'getWarehouseTransactions']);
    });

    // Stock Adjustment Routes
    Route::prefix('stock-adjustments')->group(function () {
        Route::get('/', [StockAdjustmentController::class, 'index']);
        Route::post('/', [StockAdjustmentController::class, 'store']);
        Route::get('/{id}', [StockAdjustmentController::class, 'show']);
        Route::patch('/{id}/approve', [StockAdjustmentController::class, 'approve']);
        Route::patch('/{id}/cancel', [StockAdjustmentController::class, 'cancel']);
    });
});