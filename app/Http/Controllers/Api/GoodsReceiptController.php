<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Http\Requests\GoodsReceiptRequest;
use App\Services\ReceiptNumberGenerator;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    protected $receiptNumberGenerator;
    protected $stockService;
    
    public function __construct(ReceiptNumberGenerator $receiptNumberGenerator, StockService $stockService)
    {
        $this->receiptNumberGenerator = $receiptNumberGenerator;
        $this->stockService = $stockService;
    }
    
    public function index(Request $request)
    {
        $query = GoodsReceipt::with(['vendor', 'purchaseOrder']);
        
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
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('receipt_number', 'like', "%{$search}%");
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'receipt_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $goodsReceipts = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $goodsReceipts
        ]);
    }

    public function store(GoodsReceiptRequest $request)
    {
        // Check if Purchase Order exists and is in sent or partial status
        $po = PurchaseOrder::findOrFail($request->po_id);
        if (!in_array($po->status, ['sent', 'partial'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Goods can only be received for POs in sent or partial status'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Generate receipt number
            $receiptNumber = $this->receiptNumberGenerator->generate();
            
            // Create goods receipt
            $goodsReceipt = GoodsReceipt::create([
                'receipt_number' => $receiptNumber,
                'receipt_date' => $request->receipt_date,
                'po_id' => $request->po_id,
                'vendor_id' => $po->vendor_id,
                'status' => 'pending'
            ]);
            
            // Create receipt lines
            foreach ($request->lines as $line) {
                $goodsReceipt->lines()->create([
                    'po_line_id' => $line['po_line_id'],
                    'item_id' => $line['item_id'],
                    'received_quantity' => $line['received_quantity'],
                    'warehouse_id' => $line['warehouse_id'],
                    //'location_id' => $line['location_id'] ?? null,
                    'batch_number' => $line['batch_number'] ?? null
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Goods Receipt created successfully',
                'data' => $goodsReceipt->load(['vendor', 'purchaseOrder', 'lines.item', 'lines.warehouse', 'lines.location'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Goods Receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['vendor', 'purchaseOrder', 'lines.item', 'lines.warehouse', 'lines.location']);
        
        return response()->json([
            'status' => 'success',
            'data' => $goodsReceipt
        ]);
    }

    public function update(GoodsReceiptRequest $request, GoodsReceipt $goodsReceipt)
    {
        // Check if goods receipt can be updated (only pending status)
        if ($goodsReceipt->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending Goods Receipts can be updated'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update goods receipt details
            $goodsReceipt->update([
                'receipt_date' => $request->receipt_date
            ]);
            
            // Update goods receipt lines
            if ($request->has('lines')) {
                // Delete existing lines
                $goodsReceipt->lines()->delete();
                
                // Create new lines
                foreach ($request->lines as $line) {
                    $goodsReceipt->lines()->create([
                        'po_line_id' => $line['po_line_id'],
                        'item_id' => $line['item_id'],
                        'received_quantity' => $line['received_quantity'],
                        'warehouse_id' => $line['warehouse_id'],
                        //'location_id' => $line['location_id'] ?? null,
                        'batch_number' => $line['batch_number'] ?? null
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Goods Receipt updated successfully',
                'data' => $goodsReceipt->load(['vendor', 'purchaseOrder', 'lines.item', 'lines.warehouse', 'lines.location'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Goods Receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(GoodsReceipt $goodsReceipt)
    {
        // Check if goods receipt can be deleted (only pending status)
        if ($goodsReceipt->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending Goods Receipts can be deleted'
            ], 400);
        }
        
        $goodsReceipt->lines()->delete();
        $goodsReceipt->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Goods Receipt deleted successfully'
        ]);
    }
    
    public function confirm(GoodsReceipt $goodsReceipt)
    {
        // Check if goods receipt can be confirmed (only pending status)
        if ($goodsReceipt->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending Goods Receipts can be confirmed'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update goods receipt status
            $goodsReceipt->update(['status' => 'confirmed']);
            
            // Update stock levels
            foreach ($goodsReceipt->lines as $line) {
                $this->stockService->increaseStock(
                    $line->item_id,
                    $line->warehouse_id,
                    //$line->location_id,
                    $line->received_quantity,
                    'goods_receipt',
                    $goodsReceipt->receipt_number,
                    $line->batch_number
                );
            }
            
            // Update Purchase Order status
            $this->updatePurchaseOrderStatus($goodsReceipt->purchaseOrder);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Goods Receipt confirmed successfully',
                'data' => $goodsReceipt
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to confirm Goods Receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    protected function updatePurchaseOrderStatus(PurchaseOrder $purchaseOrder)
    {
        // Get all confirmed goods receipts for this PO
        $confirmedReceipts = GoodsReceipt::where('po_id', $purchaseOrder->po_id)
                                      ->where('status', 'confirmed')
                                      ->with('lines')
                                      ->get();
        
        // Get all PO lines
        $poLines = $purchaseOrder->lines;
        
        // Check if all items have been fully received
        $allReceived = true;
        $anyReceived = false;
        
        foreach ($poLines as $poLine) {
            $receivedQty = 0;
            
            // Sum all received quantities for this PO line
            foreach ($confirmedReceipts as $receipt) {
                foreach ($receipt->lines as $receiptLine) {
                    if ($receiptLine->po_line_id === $poLine->line_id) {
                        $receivedQty += $receiptLine->received_quantity;
                    }
                }
            }
            
            if ($receivedQty > 0) {
                $anyReceived = true;
            }
            
            if ($receivedQty < $poLine->quantity) {
                $allReceived = false;
            }
        }
        
        // Update PO status based on received quantities
        if ($allReceived) {
            $purchaseOrder->update(['status' => 'received']);
        } elseif ($anyReceived) {
            $purchaseOrder->update(['status' => 'partial']);
        }
    }
}