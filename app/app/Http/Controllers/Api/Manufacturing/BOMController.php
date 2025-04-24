<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\BOM;
use App\Models\Manufacturing\BOMLine;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BOMController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = BOM::with(['item', 'unitOfMeasure']);

        // Filtering by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Search by bom_code or item name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bom_code', 'like', "%{$search}%")
                  ->orWhereHas('item', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortField = $request->get('sort_field', 'bom_code');
        $sortOrder = $request->get('sort_order', 'asc');

        // To support sorting by related item name, handle 'item.name' key
        if ($sortField === 'item.name') {
            $query->join('items', 'boms.item_id', '=', 'items.item_id')
                  ->orderBy('items.name', $sortOrder)
                  ->select('boms.*');
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        // Pagination parameters
        $perPage = $request->get('per_page', 10);

        $boms = $query->paginate($perPage);

        return response()->json([
            'data' => $boms->items(),
            'meta' => [
                'total' => $boms->total(),
                'last_page' => $boms->lastPage(),
                'current_page' => $boms->currentPage(),
                'per_page' => $boms->perPage(),
            ],
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
            'item_id' => 'required|integer|exists:items,item_id',
            'bom_code' => 'required|string|max:50',
            'revision' => 'required|string|max:10',
            'effective_date' => 'required|date',
            'status' => 'required|string|max:50',
            'standard_quantity' => 'required|numeric',
            'uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
            'bom_lines' => 'sometimes|array',
            'bom_lines.*.item_id' => 'required|integer|exists:items,item_id',
            'bom_lines.*.quantity' => 'required|numeric',
            'bom_lines.*.uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
            'bom_lines.*.is_critical' => 'sometimes|boolean',
            'bom_lines.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $bom = BOM::create([
                'item_id' => $request->item_id,
                'bom_code' => $request->bom_code,
                'revision' => $request->revision,
                'effective_date' => $request->effective_date,
                'status' => $request->status,
                'standard_quantity' => $request->standard_quantity,
                'uom_id' => $request->uom_id,
            ]);

            if ($request->has('bom_lines')) {
                foreach ($request->bom_lines as $line) {
                    BOMLine::create([
                        'bom_id' => $bom->bom_id,
                        'item_id' => $line['item_id'],
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'is_critical' => $line['is_critical'] ?? false,
                        'notes' => $line['notes'] ?? null,
                    ]);
                }
            }

            DB::commit();
            
            return response()->json([
                'data' => $bom->load('bomLines'), 
                'message' => 'BOM created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create BOM', 'error' => $e->getMessage()], 500);
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
        try {
            // Try loading only main relationships first
            $bom = BOM::with(['item', 'unitOfMeasure'])->find($id);
            
            if (!$bom) {
                return response()->json(['message' => 'BOM not found'], 404);
            }
            
            // Load bomLines and their relations separately to isolate errors
            $bom->load(['bomLines.item', 'bomLines.unitOfMeasure']);
            
            return response()->json(['data' => $bom]);
        } catch (\Exception $e) {
            // Log the exception message for debugging
            Log::error('Error fetching BOM: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch BOM', 'error' => $e->getMessage()], 500);
        }
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
        $bom = BOM::find($id);
        
        if (!$bom) {
            return response()->json(['message' => 'BOM not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|required|integer|exists:items,item_id',
            'bom_code' => 'sometimes|required|string|max:50',
            'revision' => 'sometimes|required|string|max:10',
            'effective_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|string|max:50',
            'standard_quantity' => 'sometimes|required|numeric',
            'uom_id' => 'sometimes|required|integer|exists:unit_of_measures,uom_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bom->update($request->all());
        return response()->json(['data' => $bom, 'message' => 'BOM updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $bom = BOM::find($id);
        
        if (!$bom) {
            return response()->json(['message' => 'BOM not found'], 404);
        }

        // Check if BOM is being used in Work Orders
        if ($bom->workOrders()->count() > 0) {
            return response()->json(['message' => 'Cannot delete BOM. It is being used in Work Orders.'], 400);
        }

        DB::beginTransaction();
        try {
            // Delete BOM lines first
            $bom->bomLines()->delete();
            
            // Then delete the BOM
            $bom->delete();
            
            DB::commit();
            return response()->json(['message' => 'BOM deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete BOM', 'error' => $e->getMessage()], 500);
        }
    }
}