<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VendorEvaluation;
use App\Http\Requests\VendorEvaluationRequest;
use Illuminate\Http\Request;

class VendorEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorEvaluation::with('vendor');
        
        // Apply filters
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('evaluation_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('evaluation_date', '<=', $request->date_to);
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'evaluation_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $evaluations = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $evaluations
        ]);
    }

    public function store(VendorEvaluationRequest $request)
    {
        // Calculate total score
        $totalScore = ($request->quality_score + $request->delivery_score + $request->price_score + $request->service_score) / 4;
        
        $evaluation = VendorEvaluation::create([
            'vendor_id' => $request->vendor_id,
            'evaluation_date' => $request->evaluation_date,
            'quality_score' => $request->quality_score,
            'delivery_score' => $request->delivery_score,
            'price_score' => $request->price_score,
            'service_score' => $request->service_score,
            'total_score' => $totalScore
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Evaluation created successfully',
            'data' => $evaluation->load('vendor')
        ], 201);
    }

    public function show(VendorEvaluation $vendorEvaluation)
    {
        return response()->json([
            'status' => 'success',
            'data' => $vendorEvaluation->load('vendor')
        ]);
    }

    public function update(VendorEvaluationRequest $request, VendorEvaluation $vendorEvaluation)
    {
        // Calculate total score
        $totalScore = ($request->quality_score + $request->delivery_score + $request->price_score + $request->service_score) / 4;
        
        $vendorEvaluation->update([
            'evaluation_date' => $request->evaluation_date,
            'quality_score' => $request->quality_score,
            'delivery_score' => $request->delivery_score,
            'price_score' => $request->price_score,
            'service_score' => $request->service_score,
            'total_score' => $totalScore
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Evaluation updated successfully',
            'data' => $vendorEvaluation->load('vendor')
        ]);
    }

    public function destroy(VendorEvaluation $vendorEvaluation)
    {
        $vendorEvaluation->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Evaluation deleted successfully'
        ]);
    }
    
    public function vendorPerformance(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'period' => 'nullable|in:month,quarter,year,all'
        ]);
        
        $query = VendorEvaluation::where('vendor_id', $request->vendor_id);
        
        // Apply period filter
        $period = $request->input('period', 'all');
        if ($period !== 'all') {
            $date = now();
            
            if ($period === 'month') {
                $date = $date->subMonth();
            } elseif ($period === 'quarter') {
                $date = $date->subMonths(3);
            } elseif ($period === 'year') {
                $date = $date->subYear();
            }
            
            $query->where('evaluation_date', '>=', $date);
        }
        
        $evaluations = $query->orderBy('evaluation_date', 'asc')->get();
        
        // Calculate average scores
        $avgQuality = $evaluations->avg('quality_score');
        $avgDelivery = $evaluations->avg('delivery_score');
        $avgPrice = $evaluations->avg('price_score');
        $avgService = $evaluations->avg('service_score');
        $avgTotal = $evaluations->avg('total_score');
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'evaluations' => $evaluations,
                'averages' => [
                    'quality' => round($avgQuality, 2),
                    'delivery' => round($avgDelivery, 2),
                    'price' => round($avgPrice, 2),
                    'service' => round($avgService, 2),
                    'total' => round($avgTotal, 2)
                ]
            ]
        ]);
    }
}
