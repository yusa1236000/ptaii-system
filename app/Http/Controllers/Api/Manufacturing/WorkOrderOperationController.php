<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\WorkOrder;
use App\Models\Manufacturing\WorkOrderOperation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class WorkOrderOperationController extends Controller
{
    /**
     * Display a listing of the resource for a specific work order.
     *
     * @param  int  $workOrderId
     * @return \Illuminate\Http\Response
     */
    public function index($workOrderId)
    {
        $workOrder = WorkOrder::find($workOrderId);
        
        if (!$workOrder) {
            return response()->json(['message' => 'Work order not found'], 404);
        }
        
        $operations = WorkOrderOperation::with([
                'routingOperation.workCenter', 
                'routingOperation.unitOfMeasure'
            ])
            ->where('wo_id', $workOrderId)
            ->get();
            
        return response()->json(['data' => $operations]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $workOrderId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($workOrderId, $id)
    {
        $operation = WorkOrderOperation::with([
                'routingOperation.workCenter', 
                'routingOperation.unitOfMeasure'
            ])
            ->where('wo_id', $workOrderId)
            ->where('operation_id', $id)
            ->first();
        
        if (!$operation) {
            return response()->json(['message' => 'Work order operation not found'], 404);
        }
        
        return response()->json(['data' => $operation]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workOrderId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $workOrderId, $id)
    {
        $operation = WorkOrderOperation::where('wo_id', $workOrderId)
            ->where('operation_id', $id)
            ->first();
        
        if (!$operation) {
            return response()->json(['message' => 'Work order operation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'scheduled_start' => 'sometimes|nullable|date',
            'scheduled_end' => 'sometimes|nullable|date|after_or_equal:scheduled_start',
            'actual_start' => 'sometimes|nullable|date',
            'actual_end' => 'sometimes|nullable|date|after_or_equal:actual_start',
            'actual_labor_time' => 'sometimes|numeric',
            'actual_machine_time' => 'sometimes|numeric',
            'status' => 'sometimes|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $operation->update($request->all());
        
        return response()->json([
            'data' => $operation->load([
                'routingOperation.workCenter', 
                'routingOperation.unitOfMeasure'
            ]), 
            'message' => 'Work order operation updated successfully'
        ]);
    }
}