<?php
// app/Http/Controllers/Api/ConsignmentStockController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsignmentStock;
use App\Models\Item;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConsignmentStockController extends Controller
{
    public function index(Request $request)
    {
        $query = ConsignmentStock::with(['item', 'vendor', 'warehouse']);
        
        // Apply filters
        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }
        
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('received_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('received_date', '<=', $request->date_to);
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'received_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Get results
        $consignmentStocks = $query->get();
        
        return response()->json(['data' => $consignmentStocks], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'quantity' => 'required|numeric|min:0.01',
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Create consignment stock record
            $consignmentStock = ConsignmentStock::create($request->all());
            
            // Create a stock transaction as a reference
            StockTransaction::create([
                'item_id' => $request->item_id,
                'warehouse_id' => $request->warehouse_id,
                'location_id' => null,
                'transaction_type' => 'CONSIGNMENT_IN',
                'quantity' => $request->quantity,
                'transaction_date' => $request->received_date,
                'reference_document' => 'Consignment Stock',
                'reference_number' => $consignmentStock->consignment_id
            ]);
            
            // Note: We don't update the item's current_stock since consignment stock
            // is typically not counted as part of owned inventory until consumed
            
            DB::commit();
            return response()->json(['data' => $consignmentStock->load(['item', 'vendor', 'warehouse']), 'message' => 'Consignment stock created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Error creating consignment stock: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $consignmentStock = ConsignmentStock::with(['item', 'vendor', 'warehouse'])->find($id);
        
        if (!$consignmentStock) {
            return response()->json(['message' => 'Consignment stock not found'], 404);
        }
        
        return response()->json(['data' => $consignmentStock], 200);
    }
    
    public function update(Request $request, $id)
    {
        $consignmentStock = ConsignmentStock::find($id);
        
        if (!$consignmentStock) {
            return response()->json(['message' => 'Consignment stock not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'quantity' => 'required|numeric|min:0.01',
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $oldQuantity = $consignmentStock->quantity;
        $newQuantity = $request->quantity;
        $quantityDiff = $newQuantity - $oldQuantity;
        
        DB::beginTransaction();
        try {
            // Update consignment stock record
            $consignmentStock->update($request->all());
            
            // If quantity has changed, create an adjustment transaction
            if ($quantityDiff != 0) {
                $transactionType = $quantityDiff > 0 ? 'CONSIGNMENT_ADJUST_IN' : 'CONSIGNMENT_ADJUST_OUT';
                StockTransaction::create([
                    'item_id' => $request->item_id,
                    'warehouse_id' => $request->warehouse_id,
                    'location_id' => null,
                    'transaction_type' => $transactionType,
                    'quantity' => abs($quantityDiff),
                    'transaction_date' => now(),
                    'reference_document' => 'Consignment Stock Adjustment',
                    'reference_number' => $consignmentStock->consignment_id
                ]);
            }
            
            DB::commit();
            return response()->json(['data' => $consignmentStock->load(['item', 'vendor', 'warehouse']), 'message' => 'Consignment stock updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Error updating consignment stock: ' . $e->getMessage()], 500);
        }
    }
    
    public function destroy($id)
    {
        $consignmentStock = ConsignmentStock::find($id);
        
        if (!$consignmentStock) {
            return response()->json(['message' => 'Consignment stock not found'], 404);
        }
        
        DB::beginTransaction();
        try {
            // Create a reversal transaction
            StockTransaction::create([
                'item_id' => $consignmentStock->item_id,
                'warehouse_id' => $consignmentStock->warehouse_id,
                'location_id' => null,
                'transaction_type' => 'CONSIGNMENT_OUT',
                'quantity' => -$consignmentStock->quantity,
                'transaction_date' => now(),
                'reference_document' => 'Consignment Stock Removal',
                'reference_number' => $consignmentStock->consignment_id
            ]);
            
            // Delete the consignment stock record
            $consignmentStock->delete();
            
            DB::commit();
            return response()->json(['message' => 'Consignment stock deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Error deleting consignment stock: ' . $e->getMessage()], 500);
        }
    }
    
    public function consumeConsignment(Request $request, $id)
    {
        $consignmentStock = ConsignmentStock::find($id);
        
        if (!$consignmentStock) {
            return response()->json(['message' => 'Consignment stock not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:0.01|max:' . $consignmentStock->quantity,
            'location_id' => 'nullable|exists:warehouse_locations,location_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $consumeQuantity = $request->quantity;
        $remainingQuantity = $consignmentStock->quantity - $consumeQuantity;
        
        DB::beginTransaction();
        try {
            // Create a transaction for consumption (from consignment to owned inventory)
            StockTransaction::create([
                'item_id' => $consignmentStock->item_id,
                'warehouse_id' => $consignmentStock->warehouse_id,
                'location_id' => $request->location_id,
                'transaction_type' => 'CONSIGNMENT_CONSUME',
                'quantity' => $consumeQuantity,
                'transaction_date' => now(),
                'reference_document' => 'Consignment Stock Consumption',
                'reference_number' => $consignmentStock->consignment_id
            ]);
            
            // Update the item's owned inventory
            $item = Item::find($consignmentStock->item_id);
            $item->current_stock += $consumeQuantity;
            $item->save();
            
            // Update or delete the consignment stock record
            if ($remainingQuantity > 0) {
                $consignmentStock->update(['quantity' => $remainingQuantity]);
            } else {
                $consignmentStock->delete();
            }
            
            DB::commit();
            return response()->json(['message' => 'Consignment stock consumed successfully', 'consumed_quantity' => $consumeQuantity], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Error consuming consignment stock: ' . $e->getMessage()], 500);
        }
    }
    
    public function getByVendor($vendorId)
    {
        $consignmentStocks = ConsignmentStock::with(['item', 'warehouse'])
            ->where('vendor_id', $vendorId)
            ->where('status', 'active')
            ->get();
        
        return response()->json(['data' => $consignmentStocks], 200);
    }
    
    public function getByItem($itemId)
    {
        $consignmentStocks = ConsignmentStock::with(['vendor', 'warehouse'])
            ->where('item_id', $itemId)
            ->where('status', 'active')
            ->get();
        
        return response()->json(['data' => $consignmentStocks], 200);
    }
    
    public function returnToVendor(Request $request, $id)
    {
        $consignmentStock = ConsignmentStock::find($id);
        
        if (!$consignmentStock) {
            return response()->json(['message' => 'Consignment stock not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:0.01|max:' . $consignmentStock->quantity,
            'return_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $returnQuantity = $request->quantity;
        $remainingQuantity = $consignmentStock->quantity - $returnQuantity;
        
        DB::beginTransaction();
        try {
            // Create a transaction for return
            StockTransaction::create([
                'item_id' => $consignmentStock->item_id,
                'warehouse_id' => $consignmentStock->warehouse_id,
                'location_id' => null,
                'transaction_type' => 'CONSIGNMENT_RETURN',
                'quantity' => -$returnQuantity,
                'transaction_date' => now(),
                'reference_document' => 'Consignment Stock Return',
                'reference_number' => $consignmentStock->consignment_id
            ]);
            
            // Update or delete the consignment stock record
            if ($remainingQuantity > 0) {
                $consignmentStock->update(['quantity' => $remainingQuantity]);
            } else {
                $consignmentStock->delete();
            }
            
            DB::commit();
            return response()->json(['message' => 'Consignment stock returned to vendor successfully', 'returned_quantity' => $returnQuantity], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Error returning consignment stock: ' . $e->getMessage()], 500);
        }
    }
    
    public function reportByVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $query = DB::table('consignment_stocks')
            ->join('vendors', 'consignment_stocks.vendor_id', '=', 'vendors.vendor_id')
            ->join('items', 'consignment_stocks.item_id', '=', 'items.item_id')
            ->select(
                'vendors.vendor_id',
                'vendors.name as vendor_name',
                DB::raw('COUNT(DISTINCT consignment_stocks.item_id) as item_count'),
                DB::raw('SUM(consignment_stocks.quantity) as total_quantity')
            )
            ->where('consignment_stocks.status', 'active')
            ->groupBy('vendors.vendor_id', 'vendors.name');
            
        if ($request->has('date_from')) {
            $query->whereDate('consignment_stocks.received_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('consignment_stocks.received_date', '<=', $request->date_to);
        }
        
        $report = $query->get();
        
        return response()->json(['data' => $report], 200);
    }
}