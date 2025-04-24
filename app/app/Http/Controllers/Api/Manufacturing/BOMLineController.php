<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\BOM;
use App\Models\Manufacturing\BOMLine;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class BOMLineController extends Controller
{
    /**
     * Display a listing of the resource for a specific BOM.
     *
     * @param  int  $bomId
     * @return \Illuminate\Http\Response
     */
    public function index($bomId)
    {
        $bom = BOM::find($bomId);
        
        if (!$bom) {
            return response()->json(['message' => 'BOM not found'], 404);
        }
        
        $bomLines = BOMLine::with(['item', 'unitOfMeasure'])
            ->where('bom_id', $bomId)
            ->get();
            
        return response()->json(['data' => $bomLines]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $bomId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $bomId)
    {
        $bom = BOM::find($bomId);
        
        if (!$bom) {
            return response()->json(['message' => 'BOM not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer|exists:Item,item_id',
            'quantity' => 'required|numeric',
            'uom_id' => 'required|integer|exists:UnitOfMeasure,uom_id',
            'is_critical' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bomLine = new BOMLine();
        $bomLine->bom_id = $bomId;
        $bomLine->item_id = $request->item_id;
        $bomLine->quantity = $request->quantity;
        $bomLine->uom_id = $request->uom_id;
        $bomLine->is_critical = $request->is_critical ?? false;
        $bomLine->notes = $request->notes;
        $bomLine->save();

        return response()->json([
            'data' => $bomLine->load(['item', 'unitOfMeasure']), 
            'message' => 'BOM line created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $bomId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($bomId, $id)
    {
        $bomLine = BOMLine::with(['item', 'unitOfMeasure'])
            ->where('bom_id', $bomId)
            ->where('line_id', $id)
            ->first();
        
        if (!$bomLine) {
            return response()->json(['message' => 'BOM line not found'], 404);
        }
        
        return response()->json(['data' => $bomLine]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $bomId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $bomId, $id)
    {
        $bomLine = BOMLine::where('bom_id', $bomId)
            ->where('line_id', $id)
            ->first();
        
        if (!$bomLine) {
            return response()->json(['message' => 'BOM line not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|required|integer|exists:Item,item_id',
            'quantity' => 'sometimes|required|numeric',
            'uom_id' => 'sometimes|required|integer|exists:UnitOfMeasure,uom_id',
            'is_critical' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bomLine->update($request->all());
        
        return response()->json([
            'data' => $bomLine->load(['item', 'unitOfMeasure']), 
            'message' => 'BOM line updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bomId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($bomId, $id)
    {
        $bomLine = BOMLine::where('bom_id', $bomId)
            ->where('line_id', $id)
            ->first();
        
        if (!$bomLine) {
            return response()->json(['message' => 'BOM line not found'], 404);
        }

        $bomLine->delete();
        
        return response()->json(['message' => 'BOM line deleted successfully']);
    }
}