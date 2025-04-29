<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\CycleCounting;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\StockTransaction;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CycleCountingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = CycleCounting::with(['item', 'warehouse', 'location']);
        
        // Filter by item
        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }
        
        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('count_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('count_date', '<=', $request->end_date);
        }
        
        // Pagination
        $perPage = $request->per_page ?? 15;
        $counts = $query->orderBy('count_date', 'desc')
                      ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $counts
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
            'item_id' => 'required|exists:items,item_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            //'location_id' => 'nullable|exists:warehouse_locations,location_id',
            'book_quantity' => 'required|numeric',
            'actual_quantity' => 'required|numeric',
            'count_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate location belongs to warehouse
        // if ($request->location_id) {
        //     $location = WarehouseLocation::find($request->location_id);
        //     if (!$location || $location->zone->warehouse_id != $request->warehouse_id) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Location does not belong to the specified warehouse'
        //         ], 422);
        //     }
        // }

        // Calculate variance
        $variance = $request->actual_quantity - $request->book_quantity;
        
        DB::beginTransaction();
        
        try {
            // Create the cycle count
            $count = CycleCounting::create([
                'item_id' => $request->item_id,
                'warehouse_id' => $request->warehouse_id,
                //'location_id' => $request->location_id,
                'book_quantity' => $request->book_quantity,
                'actual_quantity' => $request->actual_quantity,
                'variance' => $variance,
                'count_date' => $request->count_date,
                'status' => CycleCounting::STATUS_DRAFT
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Cycle count created successfully',
                'data' => $count
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create cycle count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $count = CycleCounting::with(['item', 'warehouse', 'location'])->find($id);
        
        if (!$count) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle count not found'
            ], 404);
        }

        // Add variance percentage
        $countData = $count->toArray();
        $countData['variance_percentage'] = $count->variance_percentage;
        $countData['is_significant'] = $count->isVarianceSignificant();

        return response()->json([
            'success' => true,
            'data' => $countData
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
        $count = CycleCounting::find($id);
        
        if (!$count) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle count not found'
            ], 404);
        }
        
        // Only allow updates for draft counts
        if ($count->status != CycleCounting::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft cycle counts can be updated'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'actual_quantity' => 'required|numeric',
            'count_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Calculate variance
        $variance = $request->actual_quantity - $count->book_quantity;
        
        DB::beginTransaction();
        
        try {
            $count->update([
                'actual_quantity' => $request->actual_quantity,
                'variance' => $variance,
                'count_date' => $request->count_date
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Cycle count updated successfully',
                'data' => $count
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cycle count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $count = CycleCounting::find($id);
        
        if (!$count) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle count not found'
            ], 404);
        }
        
        // Only allow deleting draft counts
        if ($count->status != CycleCounting::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft cycle counts can be deleted'
            ], 422);
        }

        $count->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cycle count deleted successfully'
        ]);
    }
    
    /**
     * Submit cycle count for approval
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function submit($id)
    {
        $count = CycleCounting::find($id);
        
        if (!$count) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle count not found'
            ], 404);
        }
        
        // Only allow submitting draft counts
        if ($count->status != CycleCounting::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft cycle counts can be submitted'
            ], 422);
        }
        
        $count->status = CycleCounting::STATUS_PENDING;
        $count->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Cycle count submitted for approval',
            'data' => $count
        ]);
    }
    
    /**
     * Approve cycle count and generate adjustment
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request, $id)
    {
        $count = CycleCounting::find($id);
        
        if (!$count) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle count not found'
            ], 404);
        }
        
        // Only allow approving pending counts
        if ($count->status != CycleCounting::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending cycle counts can be approved'
            ], 422);
        }
        
        $validator = Validator::make($request->all(), [
            'create_adjustment' => 'required|boolean',
            'adjustment_reason' => 'required_if:create_adjustment,true|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $count->status = CycleCounting::STATUS_APPROVED;
            $count->save();
            
            // Create stock adjustment if requested and there's a variance
            $adjustment = null;
            if ($request->create_adjustment && $count->variance != 0) {
                // Create adjustment header
                $adjustment = StockAdjustment::create([
                    'adjustment_date' => now(),
                    'adjustment_reason' => $request->adjustment_reason,
                    'status' => StockAdjustment::STATUS_APPROVED,
                    'reference_document' => 'cycle_count_' . $count->count_id
                ]);
                
                // Create adjustment line
                $line = StockAdjustmentLine::create([
                    'adjustment_id' => $adjustment->adjustment_id,
                    'item_id' => $count->item_id,
                    'warehouse_id' => $count->warehouse_id,
                    //'location_id' => $count->location_id,
                    'book_quantity' => $count->book_quantity,
                    'adjusted_quantity' => $count->actual_quantity,
                    'variance' => $count->variance
                ]);
                
                // Process the adjustment
                $adjustment->process();
            }
            
            DB::commit();
            
            $response = [
                'success' => true,
                'message' => 'Cycle count approved',
                'data' => [
                    'count' => $count
                ]
            ];
            
            if ($adjustment) {
                $response['data']['adjustment'] = $adjustment->load('adjustmentLines');
                $response['message'] .= ' and adjustment created';
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve cycle count',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reject cycle count
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reject(Request $request, $id)
    {
        $count = CycleCounting::find($id);
        
        if (!$count) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle count not found'
            ], 404);
        }
        
        // Only allow rejecting pending counts
        if ($count->status != CycleCounting::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending cycle counts can be rejected'
            ], 422);
        }
        
        $count->status = CycleCounting::STATUS_REJECTED;
        $count->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Cycle count rejected',
            'data' => $count
        ]);
    }
    
    /**
     * Generate count tasks for items
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateTasks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'count_date' => 'required|date',
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:items,item_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $warehouse = Warehouse::find($request->warehouse_id);
        $countDate = $request->count_date;
        $itemIds = $request->item_ids;
        
        $counts = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($itemIds as $itemId) {
                $item = Item::find($itemId);
                
                // Get current stock for this item in the warehouse
                $stock = StockTransaction::where('item_id', $itemId)
                    ->where('warehouse_id', $warehouse->warehouse_id)
                    ->sum('quantity');
                
                // Create a cycle count record
                $count = CycleCounting::create([
                    'item_id' => $itemId,
                    'warehouse_id' => $warehouse->warehouse_id,
                    'book_quantity' => $stock,
                    'actual_quantity' => 0, // To be filled during counting
                    'variance' => -$stock, // Initially the variance equals negative book quantity
                    'count_date' => $countDate,
                    'status' => CycleCounting::STATUS_DRAFT
                ]);
                
                $counts[] = $count;
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => count($counts) . ' cycle count tasks generated',
                'data' => $counts
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate cycle count tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}