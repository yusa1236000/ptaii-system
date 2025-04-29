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
            'item_id' => 'required|integer|exists:items,item_id',
            'quantity' => 'required|numeric|min:0',
            'uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
            'is_critical' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'is_yield_based' => 'sometimes|boolean',
            'yield_ratio' => 'required_if:is_yield_based,true|nullable|numeric|min:0',
            'shrinkage_factor' => 'nullable|numeric|min:0|max:1',
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
        
        // Set yield-based fields if provided
        $bomLine->is_yield_based = $request->is_yield_based ?? false;
        if ($bomLine->is_yield_based) {
            $bomLine->yield_ratio = $request->yield_ratio;
            $bomLine->shrinkage_factor = $request->shrinkage_factor ?? 0;
        }
        
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
            'item_id' => 'sometimes|required|integer|exists:items,item_id',
            'quantity' => 'sometimes|required|numeric|min:0',
            'uom_id' => 'sometimes|required|integer|exists:unit_of_measures,uom_id',
            'is_critical' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'is_yield_based' => 'sometimes|boolean',
            'yield_ratio' => 'required_if:is_yield_based,true|nullable|numeric|min:0',
            'shrinkage_factor' => 'nullable|numeric|min:0|max:1',
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
    
    /**
     * Calculate the potential yield from a given material quantity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $bomId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function calculateYield(Request $request, $bomId, $id)
    {
        $bomLine = BOMLine::with(['item', 'unitOfMeasure', 'bom.item'])
            ->where('bom_id', $bomId)
            ->where('line_id', $id)
            ->first();
        
        if (!$bomLine) {
            return response()->json(['message' => 'BOM line not found'], 404);
        }
        
        if (!$bomLine->is_yield_based) {
            return response()->json(['message' => 'This BOM line is not configured for yield-based calculations'], 422);
        }

        $validator = Validator::make($request->all(), [
            'material_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $materialQuantity = $request->material_quantity;
        $yield = $bomLine->calculateYield($materialQuantity);
        
        return response()->json([
            'data' => [
                'material' => [
                    'item_id' => $bomLine->item->item_id,
                    'item_code' => $bomLine->item->item_code,
                    'item_name' => $bomLine->item->name,
                    'quantity' => $materialQuantity,
                    'uom' => $bomLine->unitOfMeasure->symbol,
                ],
                'finished_product' => [
                    'item_id' => $bomLine->bom->item->item_id,
                    'item_code' => $bomLine->bom->item->item_code,
                    'item_name' => $bomLine->bom->item->name,
                    'yield_quantity' => $yield,
                    'uom' => $bomLine->bom->unitOfMeasure->symbol,
                ],
                'yield_ratio' => $bomLine->yield_ratio,
                'shrinkage_factor' => $bomLine->shrinkage_factor,
            ],
            'message' => 'Yield calculation completed successfully'
        ]);
    }
    
    /**
     * Calculate the maximum possible production from current stock levels.
     *
     * @param  int  $bomId
     * @return \Illuminate\Http\Response
     */
    public function calculateMaximumYield($bomId)
    {
        $bom = BOM::with(['item', 'unitOfMeasure', 'bomLines.item', 'bomLines.unitOfMeasure'])
            ->find($bomId);
        
        if (!$bom) {
            return response()->json(['message' => 'BOM not found'], 404);
        }
        
        $yieldBasedLines = $bom->bomLines->where('is_yield_based', true);
        
        if ($yieldBasedLines->isEmpty()) {
            return response()->json(['message' => 'No yield-based materials found in this BOM'], 422);
        }
        
        $materials = [];
        $maxProduction = null;
        
        foreach ($yieldBasedLines as $line) {
            $item = $line->item;
            $currentStock = $item->current_stock;
            $potentialYield = $line->calculateYield($currentStock);
            
            $materials[] = [
                'item_id' => $item->item_id,
                'item_code' => $item->item_code,
                'item_name' => $item->name,
                'current_stock' => $currentStock,
                'uom' => $line->unitOfMeasure->symbol,
                'yield_ratio' => $line->yield_ratio,
                'potential_yield' => $potentialYield,
            ];
            
            // Track the minimum potential yield across all materials
            // This represents the maximum possible production based on the limiting material
            if ($maxProduction === null || $potentialYield < $maxProduction) {
                $maxProduction = $potentialYield;
            }
        }
        
        return response()->json([
            'data' => [
                'finished_product' => [
                    'item_id' => $bom->item->item_id,
                    'item_code' => $bom->item->item_code,
                    'item_name' => $bom->item->name,
                    'uom' => $bom->unitOfMeasure->symbol,
                ],
                'maximum_yield' => $maxProduction,
                'materials' => $materials,
            ],
            'message' => 'Maximum yield calculation completed successfully'
        ]);
    }
}