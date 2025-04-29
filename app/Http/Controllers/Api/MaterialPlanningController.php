<?php
// app/Http/Controllers/MaterialPlanningController.php

namespace App\Http\Controllers;

use App\Models\MaterialPlan;
use App\Models\Sales\SalesForecast;
use App\Models\Item;
use App\Models\Manufacturing\BOM;
use App\Models\Manufacturing\BOMLine;
use App\Models\PurchaseRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MaterialPlanningController extends Controller
{
    /**
     * Generate material plans based on sales forecasts for finished goods
     * and calculate raw material requirements using BOM with yield-based calculations
     */
    public function generateMaterialPlans(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_period' => 'required|date',
            'buffer_percentage' => 'required|numeric|min:0|max:100',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'exists:items,item_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startPeriod = Carbon::parse($request->start_period)->startOfMonth();
        $endPeriod = $startPeriod->copy()->addMonths(5);
        $bufferPercentage = $request->buffer_percentage;

        try {
            DB::beginTransaction();
            
            // Step 1: Process Finished Goods Forecasts
            $fgPlans = $this->processFinishedGoodsForecasts(
                $startPeriod,
                $endPeriod,
                $bufferPercentage,
                $request->item_ids ?? []
            );
            
            // Step 2: Calculate Raw Material Requirements from BOM
            $rawMaterialPlans = $this->calculateRawMaterialRequirements(
                $fgPlans,
                $bufferPercentage
            );
            
            DB::commit();
            
            return response()->json([
                'message' => count($fgPlans) . " finished goods plans and " . 
                            count($rawMaterialPlans) . " raw material plans generated successfully",
                'data' => [
                    'finished_goods' => $fgPlans,
                    'raw_materials' => $rawMaterialPlans
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate material plans', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Process forecasts for finished goods
     */
    private function processFinishedGoodsForecasts($startPeriod, $endPeriod, $bufferPercentage, $itemIds = [])
    {
        // Get forecasts for the specified period
        $query = SalesForecast::whereBetween('forecast_period', [
            $startPeriod->format('Y-m-d'),
            $endPeriod->format('Y-m-d')
        ])
        ->with('item');
        
        // Filter by items if specified
        if (!empty($itemIds)) {
            $query->whereIn('item_id', $itemIds);
        }
        
        // Group forecasts by item and period
        $forecasts = $query->get()
            ->groupBy(['item_id', function($item) {
                return Carbon::parse($item->forecast_period)->format('Y-m-d');
            }]);
        
        $fgPlans = [];
        
        foreach ($forecasts as $itemId => $periodForecasts) {
            $item = Item::find($itemId);
            if (!$item) continue;
            
            // Check if this item has a BOM (is a finished good)
            $bom = $this->getActiveBOM($itemId);
            if (!$bom) continue; // Skip items without BOMs
            
            $availableStock = $item->current_stock;
            
            // Sort periods
            $periods = array_keys($periodForecasts->toArray());
            sort($periods);
            
            foreach ($periods as $period) {
                $periodForecasts = $forecasts[$itemId][$period];
                
                // Calculate total forecast for this period
                $totalForecast = $periodForecasts->sum('forecast_quantity');
                
                // Get WIP stock
                $wipStock = $this->getWIPStock($itemId, $period);
                
                // Calculate buffer
                $bufferQty = ($totalForecast * $bufferPercentage) / 100;
                
                // Calculate net requirement
                $netRequirement = $totalForecast - $availableStock - $wipStock + $bufferQty;
                $netRequirement = max(0, $netRequirement);
                
                // Create or update material plan
                $plan = MaterialPlan::updateOrCreate(
                    [
                        'item_id' => $itemId,
                        'planning_period' => $period,
                        'material_type' => 'FG', // Finished Good
                    ],
                    [
                        'forecast_quantity' => $totalForecast,
                        'available_stock' => $availableStock,
                        'wip_stock' => $wipStock,
                        'buffer_percentage' => $bufferPercentage,
                        'buffer_quantity' => $bufferQty,
                        'net_requirement' => $netRequirement,
                        'planned_order_quantity' => $netRequirement,
                        'bom_id' => $bom->bom_id,
                        'status' => 'Draft'
                    ]
                );
                
                $fgPlans[] = $plan;
                
                // Update available stock for next period
                // Available = current - forecast + planned order
                $availableStock = $availableStock - $totalForecast + $netRequirement;
            }
        }
        
        return $fgPlans;
    }
    
    /**
     * Calculate raw material requirements based on BOM (with yield-based calculations)
     */
    private function calculateRawMaterialRequirements($fgPlans, $bufferPercentage)
    {
        $rawMaterialRequirements = [];
        
        // Group FG plans by period
        $periodPlans = collect($fgPlans)->groupBy(function($plan) {
            return $plan->planning_period;
        });
        
        foreach ($periodPlans as $period => $plans) {
            $materialNeeds = [];
            
            // For each FG in the period, explode its BOM
            foreach ($plans as $plan) {
                if ($plan->net_requirement <= 0) continue;
                
                $bom = BOM::with('bomLines.item')->find($plan->bom_id);
                if (!$bom) continue;
                
                $productionQty = $plan->planned_order_quantity;
                
                // Calculate materials needed based on BOM
                foreach ($bom->bomLines as $bomLine) {
                    $materialItemId = $bomLine->item_id;
                    
                    // Check if this is a yield-based BOM line
                    if ($bomLine->is_yield_based) {
                        // For yield-based: How many units of material needed to produce desired quantity
                        // Formula: (Desired production) / (Yield ratio) / (1 - Shrinkage)
                        $shrinkageFactor = $bomLine->shrinkage_factor ?? 0;
                        $effectiveFactor = (1 - $shrinkageFactor);
                        
                        // Prevent division by zero
                        if ($bomLine->yield_ratio > 0) {
                            $requiredQty = $productionQty / $bomLine->yield_ratio / $effectiveFactor;
                        } else {
                            // Fallback to using quantity as-is if no yield ratio
                            $requiredQty = $bomLine->quantity * ($productionQty / $bom->standard_quantity);
                        }
                    } else {
                        // Traditional BOM calculation: quantity per unit * production quantity / standard quantity
                        $requiredQty = ($bomLine->quantity / $bom->standard_quantity) * $productionQty;
                    }
                    
                    // Aggregate requirements by material
                    if (!isset($materialNeeds[$materialItemId])) {
                        $materialNeeds[$materialItemId] = 0;
                    }
                    
                    $materialNeeds[$materialItemId] += $requiredQty;
                }
            }
            
            // Create material plans for raw materials
            foreach ($materialNeeds as $materialItemId => $requiredQty) {
                $material = Item::find($materialItemId);
                if (!$material) continue;
                
                $availableStock = $material->current_stock;
                
                // Calculate buffer
                $bufferQty = ($requiredQty * $bufferPercentage) / 100;
                
                // Calculate net requirement
                $netRequirement = $requiredQty - $availableStock + $bufferQty;
                $netRequirement = max(0, $netRequirement);
                
                // Create or update material plan
                $plan = MaterialPlan::updateOrCreate(
                    [
                        'item_id' => $materialItemId,
                        'planning_period' => $period,
                        'material_type' => 'RM', // Raw Material
                    ],
                    [
                        'forecast_quantity' => $requiredQty,
                        'available_stock' => $availableStock,
                        'wip_stock' => 0, // Typically not applicable for raw materials
                        'buffer_percentage' => $bufferPercentage,
                        'buffer_quantity' => $bufferQty,
                        'net_requirement' => $netRequirement,
                        'planned_order_quantity' => $netRequirement,
                        'bom_id' => null, // Raw materials don't have BOMs
                        'status' => 'Draft'
                    ]
                );
                
                $rawMaterialRequirements[] = $plan;
            }
        }
        
        return $rawMaterialRequirements;
    }
    
    /**
     * Get active BOM for an item
     */
    private function getActiveBOM($itemId)
    {
        return BOM::where('item_id', $itemId)
            ->where('status', 'Active')
            ->orderBy('effective_date', 'desc')
            ->first();
    }
    
    /**
     * Get WIP (Work in Progress) stock for an item
     */
    private function getWIPStock($itemId, $period)
    {
        $periodDate = Carbon::parse($period);
        $periodStartDate = $periodDate->copy()->startOfMonth();
        $periodEndDate = $periodDate->copy()->endOfMonth();

        // 1. Get Work Orders yang sedang dalam proses untuk item ini
        $wipFromWorkOrders = DB::table('WorkOrder')
            ->where('item_id', $itemId)
            ->whereIn('status', ['In Progress', 'Started']) // Status WIP
            ->where('planned_end_date', '<=', $periodEndDate) // Selesai dalam periode ini
            ->sum('planned_quantity');

        // 2. Get Production Orders yang sedang dalam proses
        $wipFromProductionOrders = DB::table('ProductionOrder')
            ->where('status', 'In Process')
            ->join('WorkOrder', 'ProductionOrder.wo_id', '=', 'WorkOrder.wo_id')
            ->where('WorkOrder.item_id', $itemId)
            ->where('ProductionOrder.planned_completion_date', '<=', $periodEndDate)
            ->sum(DB::raw('ProductionOrder.planned_quantity - IFNULL(ProductionOrder.actual_quantity, 0)'));

        // 3. Hitung perkiraan WIP berdasarkan persentase penyelesaian operasi
        $wipFromOperations = DB::table('WorkOrderOperation')
            ->join('WorkOrder', 'WorkOrderOperation.wo_id', '=', 'WorkOrder.wo_id')
            ->where('WorkOrder.item_id', $itemId)
            ->whereIn('WorkOrder.status', ['In Progress', 'Started'])
            ->where('WorkOrder.planned_end_date', '<=', $periodEndDate)
            ->select(
                'WorkOrder.wo_id',
                'WorkOrder.planned_quantity',
                DB::raw('COUNT(CASE WHEN WorkOrderOperation.status = "Completed" THEN 1 END) as completed_operations'),
                DB::raw('COUNT(*) as total_operations')
            )
            ->groupBy('WorkOrder.wo_id', 'WorkOrder.planned_quantity')
            ->get()
            ->map(function ($wo) {
                // Hitung persentase penyelesaian
                $completionPercent = $wo->total_operations > 0 
                    ? $wo->completed_operations / $wo->total_operations 
                    : 0;
                
                // Hitung WIP berdasarkan persentase penyelesaian
                return $wo->planned_quantity * $completionPercent;
            })
            ->sum();

        // Ambil nilai tertinggi dari ketiga metode perhitungan
        // (atau bisa juga gunakan rata-rata atau metode lain sesuai kebutuhan bisnis)
        return max($wipFromWorkOrders, $wipFromProductionOrders, $wipFromOperations);
    }
    
    /**
     * Calculate maximum production capacity based on available materials
     */
    public function calculateMaximumProduction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bom_id' => 'required|exists:boms,bom_id',
            'check_stock' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $bom = BOM::with(['item', 'unitOfMeasure', 'bomLines.item', 'bomLines.unitOfMeasure'])
                ->find($request->bom_id);
            
            if (!$bom) {
                return response()->json(['message' => 'BOM not found'], 404);
            }
            
            // Check if there are yield-based lines
            $yieldBasedLines = $bom->bomLines->where('is_yield_based', true);
            $hasYieldLines = $yieldBasedLines->count() > 0;
            
            $materials = [];
            $maxProduction = null;
            $checkStock = $request->check_stock ?? true;
            
            foreach ($bom->bomLines as $line) {
                $item = $line->item;
                $currentStock = $checkStock ? $item->current_stock : null;
                
                if ($line->is_yield_based) {
                    // For yield-based materials
                    $shrinkageFactor = $line->shrinkage_factor ?? 0;
                    $effectiveFactor = (1 - $shrinkageFactor);
                    
                    if ($checkStock && $currentStock !== null) {
                        // How many products can be made from current stock
                        $effectiveStock = $currentStock * $effectiveFactor;
                        $potentialYield = $line->yield_ratio * $effectiveStock;
                    } else {
                        // How many products could be made from the quantity in BOM
                        $effectiveQty = $line->quantity * $effectiveFactor;
                        $potentialYield = $line->yield_ratio * $effectiveQty;
                    }
                } else {
                    // For traditional BOM materials
                    if ($checkStock && $currentStock !== null) {
                        // How many standard batches can be made from current stock
                        $potentialBatches = $currentStock / $line->quantity;
                        $potentialYield = $potentialBatches * $bom->standard_quantity;
                    } else {
                        // This is fixed at standard_quantity for traditional BOM
                        $potentialYield = $bom->standard_quantity;
                    }
                }
                
                // Format material data for response
                $materialData = [
                    'item_id' => $item->item_id,
                    'item_code' => $item->item_code,
                    'item_name' => $item->name,
                    'quantity_in_bom' => $line->quantity,
                    'uom' => $line->unitOfMeasure->symbol,
                    'is_yield_based' => $line->is_yield_based
                ];
                
                if ($line->is_yield_based) {
                    $materialData['yield_ratio'] = $line->yield_ratio;
                    $materialData['shrinkage_factor'] = $line->shrinkage_factor ?? 0;
                }
                
                if ($checkStock) {
                    $materialData['current_stock'] = $currentStock;
                    $materialData['potential_yield'] = $potentialYield;
                    
                    // Track the minimum potential yield across all materials
                    // This represents the maximum possible production
                    if ($maxProduction === null || $potentialYield < $maxProduction) {
                        $maxProduction = $potentialYield;
                    }
                }
                
                $materials[] = $materialData;
            }
            
            // Result data
            $result = [
                'finished_product' => [
                    'item_id' => $bom->item->item_id,
                    'item_code' => $bom->item->item_code,
                    'item_name' => $bom->item->name,
                    'bom_id' => $bom->bom_id,
                    'bom_code' => $bom->bom_code,
                    'standard_quantity' => $bom->standard_quantity,
                    'uom' => $bom->unitOfMeasure->symbol,
                ],
                'has_yield_based_materials' => $hasYieldLines,
                'materials' => $materials
            ];
            
            if ($checkStock) {
                $result['maximum_yield'] = $maxProduction;
            }
            
            return response()->json([
                'data' => $result,
                'message' => 'Maximum production capacity calculated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to calculate production capacity', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Generate purchase requisitions from material plans
     */
    public function generatePurchaseRequisitions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|date',
            'lead_time_days' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            // Get raw material plans for the specified period
            $plans = MaterialPlan::where('planning_period', $request->period)
                ->where('material_type', 'RM')  // Only raw materials
                ->where('net_requirement', '>', 0)
                ->where('status', 'Draft')
                ->with('item')
                ->get();
            
            if ($plans->isEmpty()) {
                return response()->json(['message' => 'No material plans found requiring purchase'], 404);
            }
            
            $prNumber = $this->generatePRNumber();
            $requiredDate = Carbon::parse($request->period)->addDays($request->lead_time_days);
            
            // Create purchase requisition header
            $pr = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'pr_date' => now(),
                'requester_id' => $request->user()->id, // Assuming authentication
                'status' => 'draft',
                'notes' => 'Auto-generated from material planning'
            ]);
            
            // Create PR lines
            foreach ($plans as $plan) {
                // Create PR line
                $pr->lines()->create([
                    'item_id' => $plan->item_id,
                    'quantity' => $plan->planned_order_quantity,
                    'uom_id' => $plan->item->uom_id,
                    'required_date' => $requiredDate,
                    'notes' => "Based on material planning for {$request->period}"
                ]);
                
                // Update plan status
                $plan->update(['status' => 'Requisitioned']);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Purchase requisition generated successfully',
                'data' => $pr->load('lines')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate purchase requisition', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Generate PR number
     */
    private function generatePRNumber()
    {
        $prefix = 'PR-AUTO-';
        $date = date('Ymd');
        $lastPR = PurchaseRequisition::where('pr_number', 'like', "{$prefix}{$date}%")
            ->orderBy('pr_number', 'desc')
            ->first();
            
        if ($lastPR) {
            $lastNumber = intval(substr($lastPR->pr_number, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}