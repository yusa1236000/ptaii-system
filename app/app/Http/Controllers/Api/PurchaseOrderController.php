<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\VendorQuotation;
use App\Http\Requests\PurchaseOrderRequest;
use App\Services\PONumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    protected $poNumberGenerator;
    
    public function __construct(PONumberGenerator $poNumberGenerator)
    {
        $this->poNumberGenerator = $poNumberGenerator;
    }
    
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('po_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('po_date', '<=', $request->date_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('po_number', 'like', "%{$search}%");
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'po_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $purchaseOrders = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $purchaseOrders
        ]);
    }

    public function store(PurchaseOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Generate PO number
            $poNumber = $this->poNumberGenerator->generate();
            
            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;
            
            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }
            
            $totalAmount = $subtotal + $taxTotal;
            
            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'po_date' => $request->po_date,
                'vendor_id' => $request->vendor_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => 'draft',
                'total_amount' => $totalAmount,
                'tax_amount' => $taxTotal
            ]);
            
            // Create PO lines
            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $lineTotal = $lineSubtotal + $lineTax;
                
                $purchaseOrder->lines()->create([
                    'item_id' => $line['item_id'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'subtotal' => $lineSubtotal,
                    'tax' => $lineTax,
                    'total' => $lineTotal
                ]);
            }
            
            // If quotation_id is provided, mark quotation as accepted
            if (isset($request->quotation_id)) {
                $quotation = VendorQuotation::findOrFail($request->quotation_id);
                $quotation->update(['status' => 'accepted']);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order created successfully',
                'data' => $purchaseOrder->load(['vendor', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Purchase Order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'lines.item', 'lines.unitOfMeasure', 'goodsReceipts']);
        
        return response()->json([
            'status' => 'success',
            'data' => $purchaseOrder
        ]);
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        // Check if PO can be updated (only draft status)
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft Purchase Orders can be updated'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;
            
            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }
            
            $totalAmount = $subtotal + $taxTotal;
            
            // Update purchase order
            $purchaseOrder->update([
                'po_date' => $request->po_date,
                'vendor_id' => $request->vendor_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxTotal
            ]);
            
            // Update PO lines
            if ($request->has('lines')) {
                // Delete existing lines
                $purchaseOrder->lines()->delete();
                
                // Create new lines
                foreach ($request->lines as $line) {
                    $lineSubtotal = $line['unit_price'] * $line['quantity'];
                    $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                    $lineTotal = $lineSubtotal + $lineTax;
                    
                    $purchaseOrder->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $line['unit_price'],
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'subtotal' => $lineSubtotal,
                        'tax' => $lineTax,
                        'total' => $lineTotal
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order updated successfully',
                'data' => $purchaseOrder->load(['vendor', 'lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Purchase Order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        // Check if PO can be deleted (only draft status)
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft Purchase Orders can be deleted'
            ], 400);
        }
        
        $purchaseOrder->lines()->delete();
        $purchaseOrder->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Purchase Order deleted successfully'
        ]);
    }
    
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'status' => 'required|in:draft,submitted,approved,sent,partial,received,completed,canceled'
        ]);
        
        // Additional validations based on status transition
        $currentStatus = $purchaseOrder->status;
        $newStatus = $request->status;
        
        $validTransitions = [
            'draft' => ['submitted', 'canceled'],
            'submitted' => ['approved', 'canceled'],
            'approved' => ['sent', 'canceled'],
            'sent' => ['partial', 'received', 'canceled'],
            'partial' => ['completed', 'canceled'],
            'received' => ['completed', 'canceled'],
            'completed' => ['canceled'],
            'canceled' => []
        ];
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'status' => 'error',
                'message' => "Status cannot be changed from {$currentStatus} to {$newStatus}"
            ], 400);
        }
        
        $purchaseOrder->update(['status' => $newStatus]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Purchase Order status updated successfully',
            'data' => $purchaseOrder
        ]);
    }
    
    public function createFromQuotation(Request $request)
    {
        $request->validate([
            'quotation_id' => 'required|exists:vendor_quotations,quotation_id'
        ]);
        
        $quotation = VendorQuotation::with(['lines', 'vendor', 'requestForQuotation'])
                                  ->findOrFail($request->quotation_id);
        
        // Check if quotation is in accepted status
        if ($quotation->status !== 'accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchase Order can only be created from accepted quotations'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Generate PO number
            $poNumber = $this->poNumberGenerator->generate();
            
            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;
            
            foreach ($quotation->lines as $line) {
                $lineSubtotal = $line->unit_price * $line->quantity;
                $subtotal += $lineSubtotal;
            }
            
            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'po_date' => now(),
                'vendor_id' => $quotation->vendor_id,
                'payment_terms' => null,
                'delivery_terms' => null,
                'expected_delivery' => null,
                'status' => 'draft',
                'total_amount' => $subtotal,
                'tax_amount' => 0
            ]);
            
            // Create PO lines from quotation lines
            foreach ($quotation->lines as $line) {
                $lineSubtotal = $line->unit_price * $line->quantity;
                
                $purchaseOrder->lines()->create([
                    'item_id' => $line->item_id,
                    'unit_price' => $line->unit_price,
                    'quantity' => $line->quantity,
                    'uom_id' => $line->uom_id,
                    'subtotal' => $lineSubtotal,
                    'tax' => 0,
                    'total' => $lineSubtotal
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order created from quotation successfully',
                'data' => $purchaseOrder->load(['vendor', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Purchase Order from quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}