<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitOfMeasureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $uoms = UnitOfMeasure::all();
        
        return response()->json([
            'success' => true,
            'data' => $uoms
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
            'name' => 'required|string|max:50',
            'symbol' => 'required|string|max:10',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $uom = UnitOfMeasure::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Unit of measure created successfully',
            'data' => $uom
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
        $uom = UnitOfMeasure::with(['items', 'fromConversions', 'toConversions'])->find($id);
        
        if (!$uom) {
            return response()->json([
                'success' => false,
                'message' => 'Unit of measure not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $uom
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
        $uom = UnitOfMeasure::find($id);
        
        if (!$uom) {
            return response()->json([
                'success' => false,
                'message' => 'Unit of measure not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'symbol' => 'required|string|max:10',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $uom->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Unit of measure updated successfully',
            'data' => $uom
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
        $uom = UnitOfMeasure::find($id);
        
        if (!$uom) {
            return response()->json([
                'success' => false,
                'message' => 'Unit of measure not found'
            ], 404);
        }

        // Check if the UOM is used by any items
        if ($uom->items()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete unit of measure that is used by items'
            ], 422);
        }

        // Check if the UOM is used in any conversions
        if ($uom->fromConversions()->count() > 0 || $uom->toConversions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete unit of measure that is used in conversions'
            ], 422);
        }

        $uom->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unit of measure deleted successfully'
        ]);
    }
}