<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\QualityInspection;
use App\Models\Manufacturing\QualityParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class QualityParameterController extends Controller
{
    /**
     * Display a listing of the resource for a specific inspection.
     *
     * @param  int  $inspectionId
     * @return \Illuminate\Http\Response
     */
    public function index($inspectionId)
    {
        $inspection = QualityInspection::find($inspectionId);
        
        if (!$inspection) {
            return response()->json(['message' => 'Quality inspection not found'], 404);
        }
        
        $parameters = QualityParameter::where('inspection_id', $inspectionId)->get();
            
        return response()->json(['data' => $parameters]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $inspectionId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $inspectionId)
    {
        $inspection = QualityInspection::find($inspectionId);
        
        if (!$inspection) {
            return response()->json(['message' => 'Quality inspection not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'parameter_name' => 'required|string|max:100',
            'specification' => 'required|string',
            'actual_value' => 'required|string',
            'is_passed' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $parameter = new QualityParameter();
        $parameter->inspection_id = $inspectionId;
        $parameter->parameter_name = $request->parameter_name;
        $parameter->specification = $request->specification;
        $parameter->actual_value = $request->actual_value;
        $parameter->is_passed = $request->is_passed;
        $parameter->save();

        return response()->json([
            'data' => $parameter, 
            'message' => 'Quality parameter created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $inspectionId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($inspectionId, $id)
    {
        $parameter = QualityParameter::where('inspection_id', $inspectionId)
            ->where('parameter_id', $id)
            ->first();
        
        if (!$parameter) {
            return response()->json(['message' => 'Quality parameter not found'], 404);
        }
        
        return response()->json(['data' => $parameter]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $inspectionId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $inspectionId, $id)
    {
        $parameter = QualityParameter::where('inspection_id', $inspectionId)
            ->where('parameter_id', $id)
            ->first();
        
        if (!$parameter) {
            return response()->json(['message' => 'Quality parameter not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'parameter_name' => 'sometimes|required|string|max:100',
            'specification' => 'sometimes|required|string',
            'actual_value' => 'sometimes|required|string',
            'is_passed' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $parameter->update($request->all());
        
        return response()->json([
            'data' => $parameter, 
            'message' => 'Quality parameter updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $inspectionId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($inspectionId, $id)
    {
        $parameter = QualityParameter::where('inspection_id', $inspectionId)
            ->where('parameter_id', $id)
            ->first();
        
        if (!$parameter) {
            return response()->json(['message' => 'Quality parameter not found'], 404);
        }

        $parameter->delete();
        
        return response()->json(['message' => 'Quality parameter deleted successfully']);
    }
}