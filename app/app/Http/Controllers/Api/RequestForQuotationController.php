<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RequestForQuotation;
use App\Http\Requests\RequestForQuotationRequest;
use App\Services\RFQNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestForQuotationController extends Controller
{
    protected $rfqNumberGenerator;
    
    public function __construct(RFQNumberGenerator $rfqNumberGenerator)
    {
        $this->rfqNumberGenerator = $rfqNumberGenerator;
    }
    
    public function index(Request $request)
    {
        $query = RequestForQuotation::with(['lines.item', 'lines.unitOfMeasure']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('rfq_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('rfq_date', '<=', $request->date_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('rfq_number', 'like', "%{$search}%");
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'rfq_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $rfqs = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $rfqs
        ]);
    }

    public function store(RequestForQuotationRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Generate RFQ number
            $rfqNumber = $this->rfqNumberGenerator->generate();
            
            // Create RFQ
            $rfq = RequestForQuotation::create([
                'rfq_number' => $rfqNumber,
                'rfq_date' => $request->rfq_date,
                'validity_date' => $request->validity_date,
                'status' => 'draft'
            ]);
            
            // Create RFQ lines
            foreach ($request->lines as $line) {
                $rfq->lines()->create([
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'required_date' => $line['required_date'] ?? null
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Request For Quotation created successfully',
                'data' => $rfq->load(['lines.item', 'lines.unitOfMeasure'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Request For Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(RequestForQuotation $requestForQuotation)
    {
        $requestForQuotation->load(['lines.item', 'lines.unitOfMeasure', 'vendorQuotations.vendor']);
        
        return response()->json([
            'status' => 'success',
            'data' => $requestForQuotation
        ]);
    }

    public function update(RequestForQuotationRequest $request, RequestForQuotation $requestForQuotation)
    {
        // Check if RFQ can be updated (only draft status)
        if ($requestForQuotation->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft RFQs can be updated'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update RFQ details
            $requestForQuotation->update([
                'rfq_date' => $request->rfq_date,
                'validity_date' => $request->validity_date
            ]);
            
            // Update RFQ lines
            if ($request->has('lines')) {
                // Delete existing lines
                $requestForQuotation->lines()->delete();
                
                // Create new lines
                foreach ($request->lines as $line) {
                    $requestForQuotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'required_date' => $line['required_date'] ?? null
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Request For Quotation updated successfully',
                'data' => $requestForQuotation->load(['lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Request For Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(RequestForQuotation $requestForQuotation)
    {
        // Check if RFQ can be deleted (only draft status)
        if ($requestForQuotation->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft RFQs can be deleted'
            ], 400);
        }
        
        // Check if RFQ has vendor quotations
        if ($requestForQuotation->vendorQuotations()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'RFQ has vendor quotations and cannot be deleted'
            ], 400);
        }
        
        $requestForQuotation->lines()->delete();
        $requestForQuotation->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Request For Quotation deleted successfully'
        ]);
    }
    
    public function updateStatus(Request $request, RequestForQuotation $requestForQuotation)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,closed,canceled'
        ]);
        
        // Additional validations based on status transition
        $currentStatus = $requestForQuotation->status;
        $newStatus = $request->status;
        
        $validTransitions = [
            'draft' => ['sent', 'canceled'],
            'sent' => ['closed', 'canceled'],
            'closed' => ['canceled'],
            'canceled' => []
        ];
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'status' => 'error',
                'message' => "Status cannot be changed from {$currentStatus} to {$newStatus}"
            ], 400);
        }
        
        $requestForQuotation->update(['status' => $newStatus]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'RFQ status updated successfully',
            'data' => $requestForQuotation
        ]);
    }
}