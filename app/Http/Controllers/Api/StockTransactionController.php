<?php
// app/Http/Controllers/Api/StockTransactionController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockTransactionController extends Controller
{
    public function index()
    {
        $transactions = StockTransaction::with(['item', 'warehouse', 'location', 'batch'])->get();
        return response()->json(['data' => $transactions], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'location_id' => 'nullable|exists:warehouse_locations,location_id',
            'transaction_type' => 'required|string|max:50',
            'quantity' => 'required|numeric',
            'transaction_date' => 'required|date',
            'reference_document' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:50',
            'batch_id' => 'nullable|exists:item_batches,batch_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Create the transaction
            $transaction = StockTransaction::create($request->all());
            
            // Update item stock level
            $item = Item::find($request->item_id);
            
            // If transaction type is 'IN', add to stock, else subtract from stock
            if (in_array(strtoupper($request->transaction_type), ['IN', 'RECEIPT', 'RETURN', 'ADJUSTMENT_IN'])) {
                $item->current_stock += $request->quantity;
            } else if (in_array(strtoupper($request->transaction_type), ['OUT', 'ISSUE', 'SALE', 'ADJUSTMENT_OUT'])) {
                $item->current_stock -= $request->quantity;
            }
            
            $item->save();
            
            DB::commit();
            return response()->json(['data' => $transaction, 'message' => 'Stock transaction created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Error creating stock transaction: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $transaction = StockTransaction::with(['item', 'warehouse', 'location', 'batch'])->find($id);
        
        if (!$transaction) {
            return response()->json(['message' => 'Stock transaction not found'], 404);
        }
        
        return response()->json(['data' => $transaction], 200);
    }
    
    public function getItemTransactions($itemId)
    {
        $transactions = StockTransaction::with(['warehouse', 'location', 'batch'])
            ->where('item_id', $itemId)
            ->orderBy('transaction_date', 'desc')
            ->get();
            
        return response()->json(['data' => $transactions], 200);
    }
    
    public function getWarehouseTransactions($warehouseId)
    {
        $transactions = StockTransaction::with(['item', 'location', 'batch'])
            ->where('warehouse_id', $warehouseId)
            ->orderBy('transaction_date', 'desc')
            ->get();
            
        return response()->json(['data' => $transactions], 200);
    }
}