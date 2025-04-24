<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items = Item::with(['category', 'unitOfMeasure'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_code' => 'required|string|max:50|unique:items',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:item_categories,category_id',
            'uom_id' => 'nullable|exists:unit_of_measures,uom_id',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'is_purchasable' => 'nullable|boolean',
            'is_sellable' => 'nullable|boolean',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $item = Item::create($validator->validated());

        // Create default purchase price if provided
        if ($request->has('cost_price') && $request->cost_price > 0) {
            ItemPrice::create([
                'item_id' => $item->item_id,
                'price_type' => 'purchase',
                'price' => $request->cost_price,
                'min_quantity' => 1,
                'is_active' => true
            ]);
        }

        // Create default sale price if provided
        if ($request->has('sale_price') && $request->sale_price > 0) {
            ItemPrice::create([
                'item_id' => $item->item_id,
                'price_type' => 'sale',
                'price' => $request->sale_price,
                'min_quantity' => 1,
                'is_active' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully',
            'data' => $item
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Item::with(['category', 'unitOfMeasure', 'batches', 'stockTransactions'])->find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        // Add stock status to the response
        $item->stock_status = $item->stock_status;

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_code' => 'required|string|max:50|unique:items,item_code,' . $id . ',item_id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:item_categories,category_id',
            'uom_id' => 'nullable|exists:unit_of_measures,uom_id',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'is_purchasable' => 'nullable|boolean',
            'is_sellable' => 'nullable|boolean',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Don't allow direct update of current_stock through this endpoint
        $validated = $validator->validated();
        
        // Update default prices if provided
        $oldCostPrice = $item->cost_price;
        $oldSalePrice = $item->sale_price;
        
        $item->update($validated);
        
        // Update default purchase price record if it exists and price has changed
        if (isset($validated['cost_price']) && $validated['cost_price'] != $oldCostPrice) {
            $defaultPurchasePrice = ItemPrice::where('item_id', $item->item_id)
                ->where('price_type', 'purchase')
                ->whereNull('vendor_id')
                ->where('min_quantity', 1)
                ->first();
                
            if ($defaultPurchasePrice) {
                $defaultPurchasePrice->update(['price' => $validated['cost_price']]);
            } else {
                // Create default purchase price if it doesn't exist
                ItemPrice::create([
                    'item_id' => $item->item_id,
                    'price_type' => 'purchase',
                    'price' => $validated['cost_price'],
                    'min_quantity' => 1,
                    'is_active' => true
                ]);
            }
        }
        
        // Update default sale price record if it exists and price has changed
        if (isset($validated['sale_price']) && $validated['sale_price'] != $oldSalePrice) {
            $defaultSalePrice = ItemPrice::where('item_id', $item->item_id)
                ->where('price_type', 'sale')
                ->whereNull('customer_id')
                ->where('min_quantity', 1)
                ->first();
                
            if ($defaultSalePrice) {
                $defaultSalePrice->update(['price' => $validated['sale_price']]);
            } else {
                // Create default sale price if it doesn't exist
                ItemPrice::create([
                    'item_id' => $item->item_id,
                    'price_type' => 'sale',
                    'price' => $validated['sale_price'],
                    'min_quantity' => 1,
                    'is_active' => true
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $item
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        // Check if the item has stock transactions
        if ($item->stockTransactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete item with stock transactions'
            ], 422);
        }

        // Check if the item has batches
        if ($item->batches()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete item with existing batches'
            ], 422);
        }

        // Also check for item prices
        if ($item->prices()->count() > 0) {
            // Optionally delete all prices associated with this item
            $item->prices()->delete();
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    }

    /**
     * Get all purchasable items.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchasableItems()
    {
        $items = Item::with(['category', 'unitOfMeasure'])
            ->where('is_purchasable', true)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
    
    /**
     * Get all sellable items.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSellableItems()
    {
        $items = Item::with(['category', 'unitOfMeasure'])
            ->where('is_sellable', true)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Get stock level report
     *
     * @return \Illuminate\Http\Response
     */
    public function stockLevelReport()
    {
        $items = Item::with(['category', 'unitOfMeasure'])
            ->select('item_id', 'item_code', 'name', 'category_id', 'uom_id', 'current_stock', 'minimum_stock', 'maximum_stock')
            ->get();
        
        $stockLevels = $items->map(function ($item) {
            return [
                'item_id' => $item->item_id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'category' => $item->category ? $item->category->name : null,
                'uom' => $item->unitOfMeasure ? $item->unitOfMeasure->symbol : null,
                'current_stock' => $item->current_stock,
                'minimum_stock' => $item->minimum_stock,
                'maximum_stock' => $item->maximum_stock,
                'stock_status' => $item->stock_status
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stockLevels
        ]);
    }

    /**
     * Update stock quantity
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStock(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'adjustment_quantity' => 'required|numeric',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'location_id' => 'nullable|exists:warehouse_locations,location_id',
            'batch_id' => 'nullable|exists:item_batches,batch_id',
            'reason' => 'nullable|string',
            'reference_document' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create a stock transaction
        $transaction = $item->stockTransactions()->create([
            'warehouse_id' => $request->warehouse_id,
            'location_id' => $request->location_id,
            'transaction_type' => 'adjustment',
            'quantity' => $request->adjustment_quantity,
            'transaction_date' => now(),
            'reference_document' => $request->reference_document,
            'reference_number' => $request->reference_number,
            'batch_id' => $request->batch_id
        ]);

        // Update the item's current stock
        $item->current_stock += $request->adjustment_quantity;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'data' => [
                'item' => $item,
                'transaction' => $transaction
            ]
        ]);
    }
}