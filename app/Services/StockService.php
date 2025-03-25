<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockTransaction;
use App\Models\ItemBatch;

class StockService
{
    /**
     * Increase stock level for an item
     * 
     * @param int $itemId
     * @param int $warehouseId
     * @param int|null $locationId
     * @param float $quantity
     * @param string $transactionType
     * @param string $referenceNumber
     * @param string|null $batchNumber
     * @return StockTransaction
     */
    public function increaseStock(
        $itemId, 
        $warehouseId, 
        $locationId = null, 
        $quantity, 
        $transactionType, 
        $referenceNumber,
        $batchNumber = null
    ) {
        // Create or update batch record if batch number is provided
        $batchId = null;
        if ($batchNumber) {
            $batch = ItemBatch::firstOrCreate(
                ['item_id' => $itemId, 'batch_number' => $batchNumber],
                ['expiry_date' => null, 'manufacturing_date' => null, 'lot_number' => null]
            );
            $batchId = $batch->batch_id;
        }
        
        // Create stock transaction
        $transaction = StockTransaction::create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
            'transaction_type' => $transactionType,
            'quantity' => $quantity,
            'transaction_date' => now(),
            'reference_document' => $transactionType,
            'reference_number' => $referenceNumber,
            'batch_id' => $batchId
        ]);
        
        // Update item stock level
        $item = Item::find($itemId);
        $item->current_stock += $quantity;
        $item->save();
        
        return $transaction;
    }
    
    /**
     * Decrease stock level for an item
     * 
     * @param int $itemId
     * @param int $warehouseId
     * @param int|null $locationId
     * @param float $quantity
     * @param string $transactionType
     * @param string $referenceNumber
     * @param string|null $batchNumber
     * @return StockTransaction
     */
    public function decreaseStock(
        $itemId, 
        $warehouseId, 
        $locationId = null, 
        $quantity, 
        $transactionType, 
        $referenceNumber,
        $batchNumber = null
    ) {
        // Find batch ID if batch number is provided
        $batchId = null;
        if ($batchNumber) {
            $batch = ItemBatch::where('item_id', $itemId)
                          ->where('batch_number', $batchNumber)
                          ->first();
            
            if ($batch) {
                $batchId = $batch->batch_id;
            }
        }
        
        // Create stock transaction
        $transaction = StockTransaction::create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
            'transaction_type' => $transactionType,
            'quantity' => -$quantity,
            'transaction_date' => now(),
            'reference_document' => $transactionType,
            'reference_number' => $referenceNumber,
            'batch_id' => $batchId
        ]);
        
        // Update item stock level
        $item = Item::find($itemId);
        $item->current_stock -= $quantity;
        $item->save();
        
        return $transaction;
    }
    
    /**
     * Get current stock for an item
     * 
     * @param int $itemId
     * @return float
     */
    public function getCurrentStock($itemId)
    {
        $item = Item::find($itemId);
        return $item ? $item->current_stock : 0;
    }
    
    /**
     * Get stock by warehouse for an item
     * 
     * @param int $itemId
     * @return array
     */
    public function getStockByWarehouse($itemId)
    {
        $stockByWarehouse = StockTransaction::where('item_id', $itemId)
                                       ->select('warehouse_id')
                                       ->selectRaw('SUM(quantity) as total_quantity')
                                       ->groupBy('warehouse_id')
                                       ->with('warehouse')
                                       ->get();
        
        return $stockByWarehouse;
    }
    
    /**
     * Get stock by batch for an item
     * 
     * @param int $itemId
     * @return array
     */
    public function getStockByBatch($itemId)
    {
        $stockByBatch = StockTransaction::where('item_id', $itemId)
                                       ->whereNotNull('batch_id')
                                       ->select('batch_id')
                                       ->selectRaw('SUM(quantity) as total_quantity')
                                       ->groupBy('batch_id')
                                       ->with('batch')
                                       ->get();
        
        return $stockByBatch;
    }
}