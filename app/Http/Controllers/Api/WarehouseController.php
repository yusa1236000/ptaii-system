<?php
// app/Http/Controllers/Api/WarehouseController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::all();
        return response()->json(['data' => $warehouses], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:warehouses',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $warehouse = Warehouse::create($request->all());
        return response()->json(['data' => $warehouse, 'message' => 'Warehouse created successfully'], 201);
    }

    public function show($id)
    {
        $warehouse = Warehouse::with('zones.locations')->find($id);
        
        if (!$warehouse) {
            return response()->json(['message' => 'Warehouse not found'], 404);
        }
        
        return response()->json(['data' => $warehouse], 200);
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        
        if (!$warehouse) {
            return response()->json(['message' => 'Warehouse not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:warehouses,code,' . $id . ',warehouse_id',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $warehouse->update($request->all());
        return response()->json(['data' => $warehouse, 'message' => 'Warehouse updated successfully'], 200);
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);
        
        if (!$warehouse) {
            return response()->json(['message' => 'Warehouse not found'], 404);
        }
        
        // Check if warehouse has zones or transactions
        if ($warehouse->zones()->count() > 0 || $warehouse->stockTransactions()->count() > 0) {
            return response()->json(['message' => 'Cannot delete warehouse with zones or transactions'], 422);
        }
        
        $warehouse->delete();
        return response()->json(['message' => 'Warehouse deleted successfully'], 200);
    }
}