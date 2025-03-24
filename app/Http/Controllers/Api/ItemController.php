<?php
// app/Http/Controllers/Api/ItemController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with(['category', 'unitOfMeasure'])->get();
        return response()->json(['data' => $items], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_code' => 'required|string|max:50|unique:items',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:item_categories,category_id',
            'uom_id' => 'nullable|exists:unit_of_measures,uom_id',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = Item::create($request->all());
        return response()->json(['data' => $item, 'message' => 'Item created successfully'], 201);
    }

    public function show($id)
    {
        $item = Item::with(['category', 'unitOfMeasure', 'batches'])->find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        
        return response()->json(['data' => $item], 200);
    }

    public function update(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'item_code' => 'required|string|max:50|unique:items,item_code,' . $id . ',item_id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:item_categories,category_id',
            'uom_id' => 'nullable|exists:unit_of_measures,uom_id',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $item->update($request->all());
        return response()->json(['data' => $item, 'message' => 'Item updated successfully'], 200);
    }

    public function destroy($id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        
        // Check if item has transactions
        if ($item->stockTransactions()->count() > 0 || $item->batches()->count() > 0) {
            return response()->json(['message' => 'Cannot delete item with transactions or batches'], 422);
        }
        
        $item->delete();
        return response()->json(['message' => 'Item deleted successfully'], 200);
    }
    
    public function getStockStatus()
    {
        $items = Item::with(['category', 'unitOfMeasure'])
            ->select('item_id', 'item_code', 'name', 'category_id', 'uom_id', 'current_stock', 'minimum_stock', 'maximum_stock')
            ->get();
            
        foreach ($items as $item) {
            $item->status = 'Normal';
            
            if ($item->current_stock <= $item->minimum_stock) {
                $item->status = 'Low Stock';
            } else if ($item->current_stock >= $item->maximum_stock) {
                $item->status = 'Over Stock';
            }
        }
        
        return response()->json(['data' => $items], 200);
    }
}