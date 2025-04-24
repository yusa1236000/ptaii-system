<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Http\Requests\VendorRequest;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $vendors = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendors
        ]);
    }

    public function store(VendorRequest $request)
    {
        $vendor = Vendor::create($request->validated());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor created successfully',
            'data' => $vendor
        ], 201);
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['contracts', 'evaluations']);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendor
        ]);
    }

    public function update(VendorRequest $request, Vendor $vendor)
    {
        $vendor->update($request->validated());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor updated successfully',
            'data' => $vendor
        ]);
    }

    public function destroy(Vendor $vendor)
    {
        // Check if vendor can be deleted
        $hasRelations = $vendor->purchaseOrders()->exists() || 
                        $vendor->quotations()->exists() || 
                        $vendor->invoices()->exists();
        
        if ($hasRelations) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vendor cannot be deleted as it has related records'
            ], 400);
        }
        
        $vendor->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor deleted successfully'
        ]);
    }
    
    public function evaluations(Vendor $vendor)
    {
        $evaluations = $vendor->evaluations()->orderBy('evaluation_date', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $evaluations
        ]);
    }
    
    public function purchaseOrders(Vendor $vendor)
    {
        $purchaseOrders = $vendor->purchaseOrders()
                                ->with('lines.item')
                                ->orderBy('po_date', 'desc')
                                ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $purchaseOrders
        ]);
    }
}