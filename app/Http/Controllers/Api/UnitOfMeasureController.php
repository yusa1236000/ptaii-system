<?php
// app/Http/Controllers/Api/UnitOfMeasureController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitOfMeasureController extends Controller
{
    public function index()
    {
        $uoms = UnitOfMeasure::all();
        return response()->json(['data' => $uoms], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'symbol' => 'required|string|max:10',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uom = UnitOfMeasure::create($request->all());
        return response()->json(['data' => $uom, 'message' => 'Unit of measure created successfully'], 201);
    }

    public function show($id)
    {
        $uom = UnitOfMeasure::find($id);
        
        if (!$uom) {
            return response()->json(['message' => 'Unit of measure not found'], 404);
        }
        
        return response()->json(['data' => $uom], 200);
    }

    public function update(Request $request, $id)
    {
        $uom = UnitOfMeasure::find($id);
        
        if (!$uom) {
            return response()->json(['message' => 'Unit of measure not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'symbol' => 'required|string|max:10',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $uom->update($request->all());
        return response()->json(['data' => $uom, 'message' => 'Unit of measure updated successfully'], 200);
    }

    public function destroy($id)
    {
        $uom = UnitOfMeasure::find($id);
        
        if (!$uom) {
            return response()->json(['message' => 'Unit of measure not found'], 404);
        }
        
        // Check if UOM is being used by items
        if ($uom->items()->count() > 0) {
            return response()->json(['message' => 'Cannot delete unit of measure that is being used by items'], 422);
        }
        
        $uom->delete();
        return response()->json(['message' => 'Unit of measure deleted successfully'], 200);
    }
}