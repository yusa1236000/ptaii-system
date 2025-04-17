<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  int  $warehouseId
     * @return \Illuminate\Http\Response
     */
    public function index($warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);
        
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found'
            ], 404);
        }
        
        $zones = $warehouse->zones;
        
        return response()->json([
            'success' => true,
            'data' => $zones
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $warehouseId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);
        
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create zone with warehouse_id
        $data = $validator->validated();
        $data['warehouse_id'] = $warehouseId;
        
        $zone = WarehouseZone::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse zone created successfully',
            'data' => $zone
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $warehouseId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($warehouseId, $id)
    {
        $warehouse = Warehouse::find($warehouseId);
        
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found'
            ], 404);
        }
        
        $zone = $warehouse->zones()->with('locations')->find($id);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $zone
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $warehouseId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $warehouseId, $id)
    {
        $warehouse = Warehouse::find($warehouseId);
        
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found'
            ], 404);
        }
        
        $zone = $warehouse->zones()->find($id);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $zone->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Warehouse zone updated successfully',
            'data' => $zone
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $warehouseId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($warehouseId, $id)
    {
        $warehouse = Warehouse::find($warehouseId);
        
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found'
            ], 404);
        }
        
        $zone = $warehouse->zones()->find($id);
        
        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse zone not found'
            ], 404);
        }

        // Check if the zone has locations
        if ($zone->locations()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete zone with locations'
            ], 422);
        }

        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse zone deleted successfully'
        ]);
    }
}