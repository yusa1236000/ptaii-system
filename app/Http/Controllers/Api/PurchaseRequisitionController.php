<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequisition;
use App\Http\Requests\PurchaseRequisitionRequest;
use App\Services\PRNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    protected $prNumberGenerator;
    
    public function __construct(PRNumberGenerator $prNumberGenerator)
    {
        $this->prNumberGenerator = $prNumberGenerator;
    }
    
    public function index(Request $request)
    {
        $query = PurchaseRequisition::with(['requester', 'lines.item', 'lines.unitOfMeasure']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('requester_id')) {
            $query->where('requester_id', $request->requester_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('pr_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('pr_date', '<=', $request->date_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('pr_number', 'like', "%{$search}%");
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'pr_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $purchaseRequisitions = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $purchaseRequisitions
        ]);
    }

    public function store(PurchaseRequisitionRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Generate PR number
            $prNumber = $this->prNumberGenerator->generate();
            
            // Create purchase requisition
            $pr = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'pr_date' => $request->pr_date,
                'requester_id' => $request->requester_id,
                'status' => 'draft',
                'notes' => $request->notes
            ]);
            
            // Create PR lines
            foreach ($request->lines as $line) {
                $pr->lines()->create([
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'required_date' => $line['required_date'] ?? null,
                    'notes' => $line['notes'] ?? null
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Requisition created successfully',
                'data' => $pr->load(['requester', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Purchase Requisition',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(PurchaseRequisition $purchaseRequisition)
    {
        $purchaseRequisition->load(['requester', 'lines.item', 'lines.unitOfMeasure']);
        
        return response()->json([
            'status' => 'success',
            'data' => $purchaseRequisition
        ]);
    }

    public function update(PurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition)
    {
        // Check if PR can be updated (only draft and pending status)
        if (!in_array($purchaseRequisition->status, ['draft', 'pending'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchase Requisition cannot be updated in its current status'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update PR details
            $purchaseRequisition->update([
                'pr_date' => $request->pr_date,
                'requester_id' => $request->requester_id,
                'notes' => $request->notes
            ]);
            
            // Update PR lines
            if ($request->has('lines')) {
                // Delete existing lines
                $purchaseRequisition->lines()->delete();
                
                // Create new lines
                foreach ($request->lines as $line) {
                    $purchaseRequisition->lines()->create([
                        'item_id' => $line['item_id'],
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'required_date' => $line['required_date'] ?? null,
                        'notes' => $line['notes'] ?? null
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Requisition updated successfully',
                'data' => $purchaseRequisition->load(['requester', 'lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Purchase Requisition',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(PurchaseRequisition $purchaseRequisition)
    {
        // Check if PR can be deleted (only draft status)
        if ($purchaseRequisition->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft Purchase Requisitions can be deleted'
            ], 400);
        }
        
        $purchaseRequisition->lines()->delete();
        $purchaseRequisition->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Purchase Requisition deleted successfully'
        ]);
    }
    
    public function updateStatus(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $request->validate([
            'status' => 'required|in:draft,pending,approved,rejected,canceled'
        ]);
        
        // Additional validations based on status transition
        $currentStatus = $purchaseRequisition->status;
        $newStatus = $request->status;
        
        $validTransitions = [
            'draft' => ['pending', 'canceled'],
            'pending' => ['approved', 'rejected', 'canceled'],
            'approved' => ['canceled'],
            'rejected' => ['draft', 'canceled'],
            'canceled' => []
        ];
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'status' => 'error',
                'message' => "Status cannot be changed from {$currentStatus} to {$newStatus}"
            ], 400);
        }
        
        $purchaseRequisition->update(['status' => $newStatus]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Purchase Requisition status updated successfully',
            'data' => $purchaseRequisition
        ]);
    }
}