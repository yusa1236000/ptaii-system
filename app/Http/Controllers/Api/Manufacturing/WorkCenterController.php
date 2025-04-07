<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Models\Manufacturing\WorkCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class WorkCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $workCenters = WorkCenter::all();
        return response()->json(['data' => $workCenters]);
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
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:WorkCenter,code',
            'capacity' => 'required|numeric',
            'cost_per_hour' => 'required|numeric',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workCenter = WorkCenter::create([
            'name' => $request->name,
            'code' => $request->code,
            'capacity' => $request->capacity,
            'cost_per_hour' => $request->cost_per_hour,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'data' => $workCenter, 
            'message' => 'Work center created successfully'
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
        $workCenter = WorkCenter::find($id);
        
        if (!$workCenter) {
            return response()->json(['message' => 'Work center not found'], 404);
        }
        
        return response()->json(['data' => $workCenter]);
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
        $workCenter = WorkCenter::find($id);
        
        if (!$workCenter) {
            return response()->json(['message' => 'Work center not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'code' => 'sometimes|required|string|max:50|unique:WorkCenter,code,' . $id . ',workcenter_id',
            'capacity' => 'sometimes|required|numeric',
            'cost_per_hour' => 'sometimes|required|numeric',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workCenter->update($request->all());
        
        return response()->json([
            'data' => $workCenter, 
            'message' => 'Work center updated successfully'
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
        $workCenter = WorkCenter::find($id);
        
        if (!$workCenter) {
            return response()->json(['message' => 'Work center not found'], 404);
        }

        // Check if work center is being used in Routing Operations or Maintenance Schedules
        if ($workCenter->routingOperations()->count() > 0 || $workCenter->maintenanceSchedules()->count() > 0) {
            return response()->json(['message' => 'Cannot delete work center. It is being used in Routing Operations or Maintenance Schedules.'], 400);
        }

        $workCenter->delete();
        
        return response()->json(['message' => 'Work center deleted successfully']);
    }
}