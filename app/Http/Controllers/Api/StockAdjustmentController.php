<?php
// app/Http/Controllers/Api/StockAdjustmentController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Models\StockTransaction;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        $adjustments = StockAdjustment::with('lines')->get();
        return response()->json(['data' => $adjustments], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'adjustment_date' => 'required|date',
            'adjustment_reason' => 'nullable|string',
            'status' => 'required|string|max:50',
            'reference_document' => 'nullable|string|max:100',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'lines.*.location_id' => 'nullable|exists:warehouse_locations,location_id',
            'lines.*.book_quantity' => 'required|numeric',
            'lines.*.adjusted_quantity' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Create the adjustment header
            $adjustment = StockAdjustment::create([
                'adjustment_date' => $request->adjustment_date,
                'adjustment_reason' => $request->adjustment_reason,
                'status' => $request->status,
                'reference_document' => $request->reference_document
            ]);
            
            // Create adjustment lines and transactions
            foreach ($request->lines as $line) {
                // Calculate variance
                $variance = $line['adjusted_quantity'] - $line['book_quantity'];
                
                // Create adjustment line
                $adjustmentLine = StockAdjustmentLine::create([
                    'adjustment_id' => $adjustment->adjustment_id,
                    'item_id' => $line['item_id'],
                    'warehouse_id' => $line['warehouse_id'],
                    'location_id' => $line['location_id'] ?? null,
                    'book_quantity' => $line['book_quantity'],
                    'adjusted_quantity' => $line['adjusted_quantity'],
                    'variance' => $variance
                ]);
                
                // Create stock transaction if there's a variance
                if ($variance != 0) {
                    $transactionType = $variance > 0 ? 'ADJUSTMENT_IN' : 'ADJUSTMENT_OUT';
                    $quantity = abs($variance);
                    
                    StockTransaction::create([
                        'item_id' => $line['item_id'],
                        'warehouse_id' => $line['warehouse_id'],
                        'location_id' => $line['location_id'] ?? null,
                        'transaction_type' => $transactionType,
                        'quantity' => $quantity,
                        'transaction_date' => $request->adjustment_date,
                        'reference_document' => 'Stock Adjustment',
                        'reference_number' => $adjustment->adjustment_id
                    ]);
                    
                    // Update item stock level
                    $item = Item::find($line['item_id']);
                    $item->current_stock += $variance;
                    $item->save();
                }
            }
            
            DB::commit();
            return response()->json(['data' => $adjustment, 'message' => 'Stock adjustment created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Error creating stock adjustment: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $adjustment = StockAdjustment::with(['lines.item', 'lines.warehouse', 'lines.location'])->find($id);
        
        if (!$adjustment) {
            return response()->json(['message' => 'Stock adjustment not found'], 404);
        }
        
        return response()->json(['data' => $adjustment], 200);
    }
    
    public function approve($id)
    {
        $adjustment = StockAdjustment::find($id);
        
        if (!$adjustment) {
            return response()->json(['message' => 'Stock adjustment not found'], 404);
        }
        
        if ($adjustment->status != 'Pending') {
            return response()->json(['message' => 'Only pending adjustments can be approved'], 422);
        }
        
        $adjustment->status = 'Approved';
        $adjustment->save();
        
        return response()->json(['data' => $adjustment, 'message' => 'Stock adjustment approved successfully'], 200);
    }
    
    public function cancel($id)
    {
        $adjustment = StockAdjustment::with('lines')->find($id);
        
        if (!$adjustment) {
            return response()->json(['message' => 'Stock adjustment not found'], 404);
        }
        
        if ($adjustment->status != 'Pending') {
            return response()->json(['message' => 'Only pending adjustments can be cancelled'], 422);
        }
        
        $adjustment->status = 'Cancelled';
        $adjustment->save();
        
        return response()->json(['data' => $adjustment, 'message' => 'Stock adjustment cancelled successfully'], 200);
    }
}