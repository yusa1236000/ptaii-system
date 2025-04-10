<?php

// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemCategoryController;
use App\Http\Controllers\Api\UnitOfMeasureController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\StockTransactionController;
use App\Http\Controllers\Api\StockAdjustmentController;

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
    // Item Category Routes
    Route::prefix('item-categories')->group(function () {
        Route::get('/', [ItemCategoryController::class, 'index']);
        Route::post('/', [ItemCategoryController::class, 'store']);
        Route::get('/{id}', [ItemCategoryController::class, 'show']);
        Route::put('/{id}', [ItemCategoryController::class, 'update']);
        Route::delete('/{id}', [ItemCategoryController::class, 'destroy']);


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
});