<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
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
            'maximum_stock' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $item = Item::create($validator->validated());

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
            'maximum_stock' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Don't allow direct update of current_stock through this endpoint
        $validated = $validator->validated();
        
        $item->update($validated);

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

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully'
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