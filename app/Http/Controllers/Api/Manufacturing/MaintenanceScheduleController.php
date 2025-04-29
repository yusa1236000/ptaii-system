<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\MaintenanceSchedule;
use App\Models\Manufacturing\WorkCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class MaintenanceScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $maintenanceSchedules = MaintenanceSchedule::with('workCenter')->get();
        return response()->json(['data' => $maintenanceSchedules]);
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
            'workcenter_id' => 'required|integer|exists:WorkCenter,workcenter_id',
            'maintenance_type' => 'required|string|max:50',
            'planned_date' => 'required|date',
            'actual_date' => 'nullable|date',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maintenanceSchedule = MaintenanceSchedule::create($request->all());

        return response()->json([
            'data' => $maintenanceSchedule->load('workCenter'), 
            'message' => 'Maintenance schedule created successfully'
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
        $maintenanceSchedule = MaintenanceSchedule::with('workCenter')->find($id);
        
        if (!$maintenanceSchedule) {
            return response()->json(['message' => 'Maintenance schedule not found'], 404);
        }
        
        return response()->json(['data' => $maintenanceSchedule]);
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
        $maintenanceSchedule = MaintenanceSchedule::find($id);
        
        if (!$maintenanceSchedule) {
            return response()->json(['message' => 'Maintenance schedule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'workcenter_id' => 'sometimes|required|integer|exists:WorkCenter,workcenter_id',
            'maintenance_type' => 'sometimes|required|string|max:50',
            'planned_date' => 'sometimes|required|date',
            'actual_date' => 'nullable|date',
            'status' => 'sometimes|required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maintenanceSchedule->update($request->all());
        
        return response()->json([
            'data' => $maintenanceSchedule->load('workCenter'), 
            'message' => 'Maintenance schedule updated successfully'
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
        $maintenanceSchedule = MaintenanceSchedule::find($id);
        
        if (!$maintenanceSchedule) {
            return response()->json(['message' => 'Maintenance schedule not found'], 404);
        }

        $maintenanceSchedule->delete();
        
        return response()->json(['message' => 'Maintenance schedule deleted successfully']);
    }

    /**
     * Display a listing of the resource for a specific work center.
     *
     * @param  int  $workCenterId
     * @return \Illuminate\Http\Response
     */
    public function byWorkCenter($workCenterId)
    {
        $workCenter = WorkCenter::find($workCenterId);
        
        if (!$workCenter) {
            return response()->json(['message' => 'Work center not found'], 404);
        }
        
        $maintenanceSchedules = MaintenanceSchedule::with('workCenter')
            ->where('workcenter_id', $workCenterId)
            ->get();
            
        return response()->json(['data' => $maintenanceSchedules]);
    }
}