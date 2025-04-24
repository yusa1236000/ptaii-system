<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VendorContract;
use App\Http\Requests\VendorContractRequest;
use Illuminate\Http\Request;

class VendorContractController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorContract::with('vendor');
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->has('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('contract_number', 'like', "%{$search}%");
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'start_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $contracts = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $contracts
        ]);
    }

    public function store(VendorContractRequest $request)
    {
        $contract = VendorContract::create($request->validated());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Contract created successfully',
            'data' => $contract->load('vendor')
        ], 201);
    }

    public function show(VendorContract $vendorContract)
    {
        return response()->json([
            'status' => 'success',
            'data' => $vendorContract->load('vendor')
        ]);
    }

    public function update(VendorContractRequest $request, VendorContract $vendorContract)
    {
        $vendorContract->update($request->validated());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Contract updated successfully',
            'data' => $vendorContract->load('vendor')
        ]);
    }

    public function destroy(VendorContract $vendorContract)
    {
        // Check if contract is in draft status
        if ($vendorContract->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft contracts can be deleted'
            ], 400);
        }
        
        $vendorContract->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Contract deleted successfully'
        ]);
    }
    
    public function activate(VendorContract $vendorContract)
    {
        // Check if contract is in draft status
        if ($vendorContract->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft contracts can be activated'
            ], 400);
        }
        
        $vendorContract->update(['status' => 'active']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Contract activated successfully',
            'data' => $vendorContract
        ]);
    }
    
    public function terminate(Request $request, VendorContract $vendorContract)
    {
        $request->validate([
            'termination_date' => 'required|date'
        ]);
        
        // Check if contract is active
        if ($vendorContract->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only active contracts can be terminated'
            ], 400);
        }
        
        // Update end date and status
        $vendorContract->update([
            'end_date' => $request->termination_date,
            'status' => 'terminated'
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Contract terminated successfully',
            'data' => $vendorContract
        ]);
    }
}