<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\WarehouseZone;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  int  $zoneId
     * @return \Illuminate\Http\Response
     */
    public function index($zoneId)
    {
        $zone = WarehouseZone::find($zoneId);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }
        
        $locations = $zone->locations;
        
        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $zoneId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $zoneId)
    {
        $zone = WarehouseZone::find($zoneId);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create location with zone_id
        $data = $validator->validated();
        $data['zone_id'] = $zoneId;
        
        $location = WarehouseLocation::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse location created successfully',
            'data' => $location
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $zoneId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($zoneId, $id)
    {
        $zone = WarehouseZone::find($zoneId);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }
        
        $location = $zone->locations()->find($id);
        
        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse location not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $location
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $zoneId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $zoneId, $id)
    {
        $zone = WarehouseZone::find($zoneId);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }
        
        $location = $zone->locations()->find($id);
        
        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse location not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $location->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Warehouse location updated successfully',
            'data' => $location
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $zoneId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($zoneId, $id)
    {
        $zone = WarehouseZone::find($zoneId);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }
        
        $location = $zone->locations()->find($id);
        
        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse location not found'
            ], 404);
        }

        // Check if the location has stock transactions
        if ($location->stockTransactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with stock transactions'
            ], 422);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse location deleted successfully'
        ]);
    }
    
    /**
     * Get inventory at this location
     *
     * @param  int  $zoneId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function inventory($zoneId, $id)
    {
        $zone = WarehouseZone::find($zoneId);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }
        
        $location = $zone->locations()->find($id);
        
        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse location not found'
            ], 404);
        }
        
        // Get all stock transactions grouped by item for this location
        $inventory = $location->stockTransactions()
            ->selectRaw('item_id, SUM(quantity) as stock')
            ->groupBy('item_id')
            ->with('item.unitOfMeasure')
            ->get()
            ->map(function ($transaction) {
                return [
                    'item_id' => $transaction->item_id,
                    'item_code' => $transaction->item->item_code,
                    'name' => $transaction->item->name,
                    'uom' => $transaction->item->unitOfMeasure ? $transaction->item->unitOfMeasure->symbol : null,
                    'stock' => $transaction->stock
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'location' => $location->only(['location_id', 'code']),
                'zone' => $zone->only(['zone_id', 'name', 'code']),
                'warehouse' => $zone->warehouse->only(['warehouse_id', 'name', 'code']),
                'inventory' => $inventory
            ]
        ]);
    }
}