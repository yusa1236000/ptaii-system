<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemStock;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ItemStockController extends Controller
{
    /**
     * Display a listing of item stock.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $itemStocks = ItemStock::with(['item', 'warehouse'])
            ->where('quantity', '>', 0)
            ->get();

        return response()->json(['data' => $itemStocks], 200);
    }

    /**
     * Mendapatkan stock untuk item tertentu di semua warehouse
     *
     * @param  int  $itemId
     * @return \Illuminate\Http\Response
     */
    public function getItemStock($itemId)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }
        
        $itemStocks = ItemStock::with('warehouse')
            ->where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->get();
            
        return response()->json([
            'data' => [
                'item_id' => $item->item_id,
                'item_code' => $item->item_code,
                'item_name' => $item->name,
                'total_stock' => $itemStocks->sum('quantity'),
                'warehouse_stocks' => $itemStocks->map(function($stock) {
                    return [
                        'warehouse_id' => $stock->warehouse_id,
                        'warehouse_name' => $stock->warehouse->name,
                        'quantity' => $stock->quantity,
                        'reserved_quantity' => $stock->reserved_quantity,
                        'available_quantity' => $stock->quantity - $stock->reserved_quantity
                    ];
                })
            ]
        ], 200);
    }

    /**
     * Mendapatkan stock untuk warehouse tertentu
     *
     * @param  int  $warehouseId
     * @return \Illuminate\Http\Response
     */
    public function getWarehouseStock($warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);
        
        if (!$warehouse) {
            return response()->json(['message' => 'Warehouse tidak ditemukan'], 404);
        }
        
        $itemStocks = ItemStock::with('item')
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0)
            ->get();
            
        return response()->json([
            'data' => [
                'warehouse_id' => $warehouse->warehouse_id,
                'warehouse_name' => $warehouse->name,
                'warehouse_code' => $warehouse->code,
                'item_stocks' => $itemStocks->map(function($stock) {
                    return [
                        'item_id' => $stock->item_id,
                        'item_code' => $stock->item->item_code,
                        'item_name' => $stock->item->name,
                        'quantity' => $stock->quantity,
                        'reserved_quantity' => $stock->reserved_quantity,
                        'available_quantity' => $stock->quantity - $stock->reserved_quantity
                    ];
                })
            ]
        ], 200);
    }

    /**
     * Memindahkan stock antar warehouse
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function transferStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'from_warehouse_id' => 'required|exists:Warehouse,warehouse_id',
            'to_warehouse_id' => 'required|exists:Warehouse,warehouse_id|different:from_warehouse_id',
            'quantity' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            // Cek ketersediaan stock di warehouse asal
            $fromStock = ItemStock::where('item_id', $request->item_id)
                ->where('warehouse_id', $request->from_warehouse_id)
                ->first();
                
            if (!$fromStock || $fromStock->quantity < $request->quantity) {
                return response()->json([
                    'message' => 'Stock tidak mencukupi di warehouse asal'
                ], 400);
            }
            
            // Kurangi stock di warehouse asal
            $fromStock->quantity -= $request->quantity;
            $fromStock->save();
            
            // Buat/update stock di warehouse tujuan
            $toStock = ItemStock::firstOrNew([
                'item_id' => $request->item_id,
                'warehouse_id' => $request->to_warehouse_id
            ]);
            
            $toStock->quantity = ($toStock->quantity ?? 0) + $request->quantity;
            $toStock->save();
            
            // Buat stock transaction untuk tracking
            // Transaction dari warehouse asal (negative amount)
            StockTransaction::create([
                'item_id' => $request->item_id,
                'warehouse_id' => $request->from_warehouse_id,
                'transaction_type' => 'transfer',
                'quantity' => -$request->quantity,
                'transaction_date' => now(),
                'reference_document' => 'stock_transfer',
                'reference_number' => $request->reference_number ?? 'TR' . time()
            ]);
            
            // Transaction ke warehouse tujuan (positive amount)
            StockTransaction::create([
                'item_id' => $request->item_id,
                'warehouse_id' => $request->to_warehouse_id,
                'transaction_type' => 'transfer',
                'quantity' => $request->quantity,
                'transaction_date' => now(),
                'reference_document' => 'stock_transfer',
                'reference_number' => $request->reference_number ?? 'TR' . time()
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Stock berhasil dipindahkan',
                'data' => [
                    'from_warehouse' => [
                        'warehouse_id' => $fromStock->warehouse_id,
                        'remaining_quantity' => $fromStock->quantity
                    ],
                    'to_warehouse' => [
                        'warehouse_id' => $toStock->warehouse_id,
                        'new_quantity' => $toStock->quantity
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memindahkan stock', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Menyesuaikan (adjust) stock item di warehouse tertentu
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function adjustStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'warehouse_id' => 'required|exists:Warehouse,warehouse_id',
            'new_quantity' => 'required|numeric|min:0',
            'reason' => 'required|string',
            'reference_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            // Ambil data stock current
            $itemStock = ItemStock::firstOrNew([
                'item_id' => $request->item_id,
                'warehouse_id' => $request->warehouse_id
            ]);
            
            $oldQuantity = $itemStock->quantity ?? 0;
            $adjustmentQuantity = $request->new_quantity - $oldQuantity;
            
            // Update quantity
            $itemStock->quantity = $request->new_quantity;
            $itemStock->save();
            
            // Buat stock transaction untuk adjustment
            StockTransaction::create([
                'item_id' => $request->item_id,
                'warehouse_id' => $request->warehouse_id,
                'transaction_type' => 'adjustment',
                'quantity' => $adjustmentQuantity,
                'transaction_date' => now(),
                'reference_document' => 'stock_adjustment',
                'reference_number' => $request->reference_number ?? 'ADJ' . time(),
                'notes' => $request->reason . ($request->notes ? ' - ' . $request->notes : '')
            ]);
            
            // Update item's current_stock
            $item = Item::find($request->item_id);
            $item->current_stock += $adjustmentQuantity;
            $item->save();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Stock berhasil disesuaikan',
                'data' => [
                    'item_id' => $itemStock->item_id,
                    'warehouse_id' => $itemStock->warehouse_id,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $itemStock->quantity,
                    'adjustment' => $adjustmentQuantity
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyesuaikan stock', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mereservasi stock untuk pemenuhan SO atau kebutuhan lain
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reserveStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'warehouse_id' => 'required|exists:Warehouse,warehouse_id',
            'quantity' => 'required|numeric|min:0.01',
            'reference_type' => 'required|string|max:50',
            'reference_id' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            // Cek ketersediaan stock
            $itemStock = ItemStock::where('item_id', $request->item_id)
                ->where('warehouse_id', $request->warehouse_id)
                ->first();
                
            if (!$itemStock || ($itemStock->quantity - $itemStock->reserved_quantity) < $request->quantity) {
                return response()->json([
                    'message' => 'Stock yang tersedia tidak mencukupi untuk direservasi'
                ], 400);
            }
            
            // Update reserved quantity
            $itemStock->reserved_quantity += $request->quantity;
            $itemStock->save();
            
            // Buat reservation record (jika ada model untuk ini)
            // StockReservation::create([
            //     'item_id' => $request->item_id,
            //     'warehouse_id' => $request->warehouse_id,
            //     'quantity' => $request->quantity,
            //     'reference_type' => $request->reference_type,
            //     'reference_id' => $request->reference_id,
            //     'reservation_date' => now()
            // ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Stock berhasil direservasi',
                'data' => [
                    'item_id' => $itemStock->item_id,
                    'warehouse_id' => $itemStock->warehouse_id,
                    'total_quantity' => $itemStock->quantity,
                    'reserved_quantity' => $itemStock->reserved_quantity,
                    'available_quantity' => $itemStock->quantity - $itemStock->reserved_quantity
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mereservasi stock', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Batalkan reservasi stock
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function releaseReservation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'warehouse_id' => 'required|exists:Warehouse,warehouse_id',
            'quantity' => 'required|numeric|min:0.01',
            'reference_type' => 'required|string|max:50',
            'reference_id' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            // Ambil data stock
            $itemStock = ItemStock::where('item_id', $request->item_id)
                ->where('warehouse_id', $request->warehouse_id)
                ->first();
                
            if (!$itemStock || $itemStock->reserved_quantity < $request->quantity) {
                return response()->json([
                    'message' => 'Jumlah reservasi tidak mencukupi untuk dilepaskan'
                ], 400);
            }
            
            // Update reserved quantity
            $itemStock->reserved_quantity -= $request->quantity;
            $itemStock->save();
            
            // Hapus atau update reservation record (jika ada model untuk ini)
            // StockReservation::where([
            //     'item_id' => $request->item_id,
            //     'warehouse_id' => $request->warehouse_id,
            //     'reference_type' => $request->reference_type,
            //     'reference_id' => $request->reference_id,
            // ])->delete();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Reservasi stock berhasil dilepaskan',
                'data' => [
                    'item_id' => $itemStock->item_id,
                    'warehouse_id' => $itemStock->warehouse_id,
                    'total_quantity' => $itemStock->quantity,
                    'reserved_quantity' => $itemStock->reserved_quantity,
                    'available_quantity' => $itemStock->quantity - $itemStock->reserved_quantity
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal melepaskan reservasi stock', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Display items with negative stock.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNegativeStocks()
    {
        $negativeStocks = ItemStock::with(['item', 'warehouse'])
            ->where('quantity', '<', 0)
            ->get();

        $result = $negativeStocks->map(function($stock) {
            return [
                'stock_id' => $stock->stock_id,
                'item_id' => $stock->item_id,
                'item_code' => $stock->item->item_code,
                'item_name' => $stock->item->name,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,
                'quantity' => $stock->quantity,
                'reserved_quantity' => $stock->reserved_quantity,
                'available_quantity' => $stock->quantity - $stock->reserved_quantity
            ];
        });

        return response()->json([
            'data' => $result,
            'count' => count($result)
        ], 200);
    }

    /**
     * Get summary of negative stock value.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNegativeStockSummary()
    {
        // Get all negative stocks
        $negativeStocks = ItemStock::with(['item', 'warehouse'])
            ->where('quantity', '<', 0)
            ->get();
            
        // Total negative quantity
        $totalNegativeQty = $negativeStocks->sum('quantity');
        
        // Total negative value (quantity * cost price)
        $totalNegativeValue = 0;
        foreach ($negativeStocks as $stock) {
            $cost = $stock->item->cost_price ?? 0;
            $totalNegativeValue += $stock->quantity * $cost;
        }
        
        // Count by warehouse
        $warehouseCounts = [];
        foreach ($negativeStocks->groupBy('warehouse_id') as $warehouseId => $stocks) {
            $warehouse = $stocks->first()->warehouse;
            $warehouseCounts[] = [
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $warehouse->name,
                'item_count' => $stocks->count(),
                'total_negative_quantity' => $stocks->sum('quantity')
            ];
        }
        
        return response()->json([
            'data' => [
                'total_negative_items' => $negativeStocks->count(),
                'total_negative_quantity' => $totalNegativeQty,
                'total_negative_value' => $totalNegativeValue,
                'warehouse_summary' => $warehouseCounts
            ]
        ], 200);
    }
}