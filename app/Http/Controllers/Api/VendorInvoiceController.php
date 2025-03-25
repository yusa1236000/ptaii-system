<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VendorInvoice;
use App\Models\PurchaseOrder;
use App\Http\Requests\VendorInvoiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorInvoice::with(['vendor', 'purchaseOrder']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->has('po_id')) {
            $query->where('po_id', $request->po_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('invoice_number', 'like', "%{$search}%");
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'invoice_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $vendorInvoices = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendorInvoices
        ]);
    }

    public function store(VendorInvoiceRequest $request)
    {
        // Check if Purchase Order exists and is in appropriate status
        $po = PurchaseOrder::findOrFail($request->po_id);
        if (!in_array($po->status, ['partial', 'received', 'completed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoices can only be created for POs that have received goods'
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
            
            // Create vendor invoice
            $vendorInvoice = VendorInvoice::create([
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'vendor_id' => $po->vendor_id,
                'po_id' => $request->po_id,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxTotal,
                'due_date' => $request->due_date,
                'status' => 'pending'
            ]);
            
            // Create invoice lines
            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $lineTotal = $lineSubtotal + $lineTax;
                
                $vendorInvoice->lines()->create([
                    'po_line_id' => $line['po_line_id'],
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $lineSubtotal,
                    'tax' => $lineTax,
                    'total' => $lineTotal
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor Invoice created successfully',
                'data' => $vendorInvoice->load(['vendor', 'purchaseOrder', 'lines.item'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Vendor Invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(VendorInvoice $vendorInvoice)
    {
        $vendorInvoice->load(['vendor', 'purchaseOrder', 'lines.item']);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendorInvoice
        ]);
    }

    public function update(VendorInvoiceRequest $request, VendorInvoice $vendorInvoice)
    {
        // Check if invoice can be updated (only pending status)
        if ($vendorInvoice->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending invoices can be updated'
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
            
            // Update vendor invoice
            $vendorInvoice->update([
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'total_amount' => $totalAmount,
                'tax_amount' => $tax### `app/Http/Controllers/API/VendorQuotationController.php`

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VendorQuotation;
use App\Models\RequestForQuotation;
use App\Http\Requests\VendorQuotationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorQuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorQuotation::with(['vendor', 'requestForQuotation']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->has('rfq_id')) {
            $query->where('rfq_id', $request->rfq_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('quotation_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('quotation_date', '<=', $request->date_to);
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'quotation_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $vendorQuotations = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendorQuotations
        ]);
    }

    public function store(VendorQuotationRequest $request)
    {
        // Check if RFQ exists and is in sent status
        $rfq = RequestForQuotation::findOrFail($request->rfq_id);
        if ($rfq->status !== 'sent') {
            return response()->json([
                'status' => 'error',
                'message' => 'Vendor quotations can only be created for RFQs in sent status'
            ], 400);
        }
        
        // Check if vendor quotation already exists for this vendor and RFQ
        $exists = VendorQuotation::where('rfq_id', $request->rfq_id)
                                 ->where('vendor_id', $request->vendor_id)
                                 ->exists();
        
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'A quotation from this vendor for this RFQ already exists'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Create vendor quotation
            $vendorQuotation = VendorQuotation::create([
                'rfq_id' => $request->rfq_id,
                'vendor_id' => $request->vendor_id,
                'quotation_date' => $request->quotation_date,
                'validity_date' => $request->validity_date,
                'status' => 'received'
            ]);
            
            // Create quotation lines
            foreach ($request->lines as $line) {
                $vendorQuotation->lines()->create([
                    'item_id' => $line['item_id'],
                    'unit_price' => $line['unit_price'],
                    'uom_id' => $line['uom_id'],
                    'quantity' => $line['quantity'],
                    'delivery_date' => $line['delivery_date'] ?? null
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor Quotation created successfully',
                'data' => $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Vendor Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(VendorQuotation $vendorQuotation)
    {
        $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure']);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendorQuotation
        ]);
    }

    public function update(VendorQuotationRequest $request, VendorQuotation $vendorQuotation)
    {
        // Check if quotation can be updated (only received status)
        if ($vendorQuotation->status !== 'received') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only received quotations can be updated'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update quotation details
            $vendorQuotation->update([
                'quotation_date' => $request->quotation_date,
                'validity_date' => $request->validity_date
            ]);
            
            // Update quotation lines
            if ($request->has('lines')) {
                // Delete existing lines
                $vendorQuotation->lines()->delete();
                
                // Create new lines
                foreach ($request->lines as $line) {
                    $vendorQuotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $line['unit_price'],
                        'uom_id' => $line['uom_id'],
                        'quantity' => $line['quantity'],
                        'delivery_date' => $line['delivery_date'] ?? null
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor Quotation updated successfully',
                'data' => $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Vendor Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(VendorQuotation $vendorQuotation)
    {
        // Check if quotation can be deleted (only received status)
        if ($vendorQuotation->status !== 'received') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only received quotations can be deleted'
            ], 400);
        }
        
        $vendorQuotation->lines()->delete();
        $vendorQuotation->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Quotation deleted successfully'
        ]);
    }
    
    public function updateStatus(Request $request, VendorQuotation $vendorQuotation)
    {
        $request->validate([
            'status' => 'required|in:received,accepted,rejected'
        ]);
        
        // Additional validations based on status transition
        $currentStatus = $vendorQuotation->status;
        $newStatus = $request->status;
        
        $validTransitions = [
            'received' => ['accepted', 'rejected'],
            'accepted' => ['rejected'],
            'rejected' => ['accepted']
        ];
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'status' => 'error',
                'message' => "Status cannot be changed from {$currentStatus} to {$newStatus}"
            ], 400);
        }
        
        $vendorQuotation->update(['status' => $newStatus]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Quotation status updated successfully',
            'data' => $vendorQuotation
        ]);
    }
}