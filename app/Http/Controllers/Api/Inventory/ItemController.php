<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\Manufacturing\BOM;
use App\Models\Manufacturing\BOMLine;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items = Item::with(['category', 'unitOfMeasure'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_code' => 'required|string|max:50|unique:items',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:item_categories,category_id',
            'uom_id' => 'nullable|exists:unit_of_measures,uom_id',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'is_purchasable' => ['nullable', 'in:true,false'],
            'is_sellable' => ['nullable', 'in:true,false'],
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price_currency' => 'nullable|string|size:3',
            'sale_price_currency' => 'nullable|string|size:3',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'thickness' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'document' => 'nullable|file|mimes:pdf|max:10240' // Accept PDF files up to 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle document upload if present
        $documentPath = null;
        if ($request->hasFile('document') && $request->file('document')->isValid()) {
            $file = $request->file('document');
            $fileName = 'item_' . time() . '_' . $file->getClientOriginalName();
            $documentPath = $file->storeAs('item_documents', $fileName, 'public');
        }

        // Prepare validated data
        $itemData = $validator->validated();
        unset($itemData['document']); // Remove file from validated data
        $itemData['document_path'] = $documentPath;
        
        // Set default currencies if not provided
        $baseCurrency = config('app.base_currency', 'USD');
        $itemData['cost_price_currency'] = $request->cost_price_currency ?? $baseCurrency;
        $itemData['sale_price_currency'] = $request->sale_price_currency ?? $baseCurrency;

        $item = Item::create($itemData);

        // Create default purchase price if provided
        if ($request->has('cost_price') && $request->cost_price > 0) {
            // Convert to base currency if needed
            $baseCostPrice = $request->cost_price;
            if ($itemData['cost_price_currency'] !== $baseCurrency) {
                $rate = CurrencyRate::getCurrentRate($itemData['cost_price_currency'], $baseCurrency);
                if ($rate) {
                    $baseCostPrice = $request->cost_price * $rate;
                }
            }
            
            ItemPrice::create([
                'item_id' => $item->item_id,
                'price_type' => 'purchase',
                'price' => $request->cost_price,
                'currency_code' => $itemData['cost_price_currency'],
                'base_currency_price' => $baseCostPrice,
                'base_currency' => $baseCurrency,
                'min_quantity' => 1,
                'is_active' => true
            ]);
        }

        // Create default sale price if provided
        if ($request->has('sale_price') && $request->sale_price > 0) {
            // Convert to base currency if needed
            $baseSalePrice = $request->sale_price;
            if ($itemData['sale_price_currency'] !== $baseCurrency) {
                $rate = CurrencyRate::getCurrentRate($itemData['sale_price_currency'], $baseCurrency);
                if ($rate) {
                    $baseSalePrice = $request->sale_price * $rate;
                }
            }
            
            ItemPrice::create([
                'item_id' => $item->item_id,
                'price_type' => 'sale',
                'price' => $request->sale_price,
                'currency_code' => $itemData['sale_price_currency'],
                'base_currency_price' => $baseSalePrice,
                'base_currency' => $baseCurrency,
                'min_quantity' => 1,
                'is_active' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully',
            'data' => $item
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Item::with(['category', 'unitOfMeasure', 'batches', 'stockTransactions'])->find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        // Add stock status to the response
        $item->stock_status = $item->stock_status;
        
        // Get BOM components if this is a Finished Good
        $bomComponents = [];
        if ($item->category && $item->category->category_id === '1') {
            // Get the active BOM
            $activeBom = BOM::where('item_id', $item->item_id)
                ->where('status', 'Active')
                ->orderBy('effective_date', 'desc')
                ->first();
                
            if ($activeBom) {
                $bomComponents = BOMLine::with(['item', 'unitOfMeasure'])
                    ->where('bom_id', $activeBom->bom_id)
                    ->get()
                    ->map(function ($line) {
                        return [
                            'component_id' => $line->item_id,
                            'component_code' => $line->item->item_code,
                            'component_name' => $line->item->name,
                            'quantity' => $line->quantity,
                            'uom' => $line->unitOfMeasure ? $line->unitOfMeasure->symbol : null,
                            'is_critical' => $line->is_critical
                        ];
                    });
            }
        }

        // Add document URL if document exists
        if ($item->document_path) {
            $item->document_url = url('storage/' . $item->document_path);
        }

        return response()->json([
            'success' => true,
            'data' => $item,
            'bom_components' => $bomComponents
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_code' => 'required|string|max:50|unique:items,item_code,' . $id . ',item_id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:item_categories,category_id',
            'uom_id' => 'nullable|exists:unit_of_measures,uom_id',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'is_purchasable' => ['nullable', 'in:true,false,1,0'],
            'is_sellable' => ['nullable', 'in:true,false,1,0'],
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price_currency' => 'nullable|string|size:3',
            'sale_price_currency' => 'nullable|string|size:3',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'thickness' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'document' => 'nullable|file|mimes:pdf|max:10240' // Accept PDF files up to 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Don't allow direct update of current_stock through this endpoint
        $validated = $validator->validated();
        unset($validated['document']); // Remove document from validated data

        // Convert is_purchasable and is_sellable to boolean explicitly
        if (isset($validated['is_purchasable'])) {
            $validated['is_purchasable'] = filter_var($validated['is_purchasable'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($validated['is_sellable'])) {
            $validated['is_sellable'] = filter_var($validated['is_sellable'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Update default prices if provided
        $oldCostPrice = $item->cost_price;
        $oldSalePrice = $item->sale_price;
        $oldCostPriceCurrency = $item->cost_price_currency;
        $oldSalePriceCurrency = $item->sale_price_currency;
        $baseCurrency = config('app.base_currency', 'USD');
        
        // Handle document upload if present
        if ($request->hasFile('document') && $request->file('document')->isValid()) {
            // Delete old document if exists
            if ($item->document_path && Storage::disk('public')->exists($item->document_path)) {
                Storage::disk('public')->delete($item->document_path);
            }
            
            $file = $request->file('document');
            $fileName = 'item_' . time() . '_' . $file->getClientOriginalName();
            $documentPath = $file->storeAs('item_documents', $fileName, 'public');
            $validated['document_path'] = $documentPath;
        }
        
        $item->update($validated);
        
        // Update default purchase price record if it exists and price has changed
        if ((isset($validated['cost_price']) && $validated['cost_price'] != $oldCostPrice) ||
            (isset($validated['cost_price_currency']) && $validated['cost_price_currency'] != $oldCostPriceCurrency)) {
            
            $costPrice = $validated['cost_price'] ?? $oldCostPrice;
            $costPriceCurrency = $validated['cost_price_currency'] ?? $oldCostPriceCurrency ?? $baseCurrency;
            
            // Convert to base currency if needed
            $baseCostPrice = $costPrice;
            if ($costPriceCurrency !== $baseCurrency) {
                $rate = CurrencyRate::getCurrentRate($costPriceCurrency, $baseCurrency);
                if ($rate) {
                    $baseCostPrice = $costPrice * $rate;
                }
            }
            
            $defaultPurchasePrice = ItemPrice::where('item_id', $item->item_id)
                ->where('price_type', 'purchase')
                ->whereNull('vendor_id')
                ->where('min_quantity', 1)
                ->first();
                
            if ($defaultPurchasePrice) {
                $defaultPurchasePrice->update([
                    'price' => $costPrice,
                    'currency_code' => $costPriceCurrency,
                    'base_currency_price' => $baseCostPrice,
                    'base_currency' => $baseCurrency
                ]);
            } else {
                // Create default purchase price if it doesn't exist
                ItemPrice::create([
                    'item_id' => $item->item_id,
                    'price_type' => 'purchase',
                    'price' => $costPrice,
                    'currency_code' => $costPriceCurrency,
                    'base_currency_price' => $baseCostPrice,
                    'base_currency' => $baseCurrency,
                    'min_quantity' => 1,
                    'is_active' => true
                ]);
            }
        }
        
        // Update default sale price record if it exists and price has changed
        if ((isset($validated['sale_price']) && $validated['sale_price'] != $oldSalePrice) ||
            (isset($validated['sale_price_currency']) && $validated['sale_price_currency'] != $oldSalePriceCurrency)) {
            
            $salePrice = $validated['sale_price'] ?? $oldSalePrice;
            $salePriceCurrency = $validated['sale_price_currency'] ?? $oldSalePriceCurrency ?? $baseCurrency;
            
            // Convert to base currency if needed
            $baseSalePrice = $salePrice;
            if ($salePriceCurrency !== $baseCurrency) {
                $rate = CurrencyRate::getCurrentRate($salePriceCurrency, $baseCurrency);
                if ($rate) {
                    $baseSalePrice = $salePrice * $rate;
                }
            }
            
            $defaultSalePrice = ItemPrice::where('item_id', $item->item_id)
                ->where('price_type', 'sale')
                ->whereNull('customer_id')
                ->where('min_quantity', 1)
                ->first();
                
            if ($defaultSalePrice) {
                $defaultSalePrice->update([
                    'price' => $salePrice,
                    'currency_code' => $salePriceCurrency,
                    'base_currency_price' => $baseSalePrice,
                    'base_currency' => $baseCurrency
                ]);
            } else {
                // Create default sale price if it doesn't exist
                ItemPrice::create([
                    'item_id' => $item->item_id,
                    'price_type' => 'sale',
                    'price' => $salePrice,
                    'currency_code' => $salePriceCurrency,
                    'base_currency_price' => $baseSalePrice,
                    'base_currency' => $baseCurrency,
                    'min_quantity' => 1,
                    'is_active' => true
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $item
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        // Check if the item has stock transactions
        if ($item->stockTransactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete item with stock transactions'
            ], 422);
        }

        // Check if the item has batches
        if ($item->batches()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete item with existing batches'
            ], 422);
        }

        // Delete document if exists
        if ($item->document_path && Storage::disk('public')->exists($item->document_path)) {
            Storage::disk('public')->delete($item->document_path);
        }

        // Also check for item prices
        if ($item->prices()->count() > 0) {
            // Optionally delete all prices associated with this item
            $item->prices()->delete();
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    }

    /**
     * Download item document
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadDocument($id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        if (!$item->document_path) {
            return response()->json([
                'success' => false,
                'message' => 'This item has no document'
            ], 404);
        }

        if (!Storage::disk('public')->exists($item->document_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found'
            ], 404);
        }

        return Storage::disk('public')->download($item->document_path, $item->name . '.pdf');
    }

    /**
     * Get all purchasable items.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchasableItems()
    {
        $items = Item::with(['category', 'unitOfMeasure'])
            ->where('is_purchasable', true)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
    
    /**
     * Get all sellable items.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSellableItems()
    {
        $items = Item::with(['category', 'unitOfMeasure'])
            ->where('is_sellable', true)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Get stock level report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function stockLevelReport(Request $request)
    {
        $items = Item::with(['category', 'unitOfMeasure'])
            ->select('item_id', 'item_code', 'name', 'category_id', 'uom_id', 'current_stock', 'minimum_stock', 'maximum_stock', 'cost_price', 'cost_price_currency')
            ->get();
        
        $baseCurrency = config('app.base_currency', 'USD');
        $reportCurrency = $request->currency ?? $baseCurrency;
        
        $stockLevels = $items->map(function ($item) use ($reportCurrency, $baseCurrency) {
            // Get cost price in report currency
            $costPrice = $item->cost_price;
            $costPriceCurrency = $item->cost_price_currency ?? $baseCurrency;
            
            if ($costPriceCurrency !== $reportCurrency) {
                $costPrice = $item->getDefaultPurchasePriceInCurrency($reportCurrency);
            }
            
            // Calculate stock value
            $stockValue = $item->current_stock * $costPrice;
            
            return [
                'item_id' => $item->item_id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'category' => $item->category ? $item->category->name : null,
                'uom' => $item->unitOfMeasure ? $item->unitOfMeasure->symbol : null,
                'current_stock' => $item->current_stock,
                'minimum_stock' => $item->minimum_stock,
                'maximum_stock' => $item->maximum_stock,
                'stock_status' => $item->stock_status,
                'cost_price' => $costPrice,
                'cost_price_currency' => $reportCurrency,
                'stock_value' => $stockValue,
                'stock_value_currency' => $reportCurrency
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stockLevels,
            'currency' => $reportCurrency
        ]);
    }

    /**
     * Update stock quantity
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStock(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'adjustment_quantity' => 'required|numeric',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'location_id' => 'nullable|exists:warehouse_locations,location_id',
            'batch_id' => 'nullable|exists:item_batches,batch_id',
            'reason' => 'nullable|string',
            'reference_document' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create a stock transaction
        $transaction = $item->stockTransactions()->create([
            'warehouse_id' => $request->warehouse_id,
            'location_id' => $request->location_id,
            'transaction_type' => 'adjustment',
            'quantity' => $request->adjustment_quantity,
            'transaction_date' => now(),
            'reference_document' => $request->reference_document,
            'reference_number' => $request->reference_number,
            'batch_id' => $request->batch_id
        ]);

        // Update the item's current stock
        $item->current_stock += $request->adjustment_quantity;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'data' => [
                'item' => $item,
                'transaction' => $transaction
            ]
        ]);
    }
    
    /**
     * Get item price in multiple currencies
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getPricesInCurrencies(Request $request, $id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'currencies' => 'required|array',
            'currencies.*' => 'required|string|size:3',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $date = $request->date ?? now()->format('Y-m-d');
        $currencies = $request->currencies;
        $baseCurrency = config('app.base_currency', 'USD');
        
        $priceData = [];
        
        foreach ($currencies as $currency) {
            // Get purchase price in requested currency
            $purchasePrice = $item->getDefaultPurchasePriceInCurrency($currency, $date);
            
            // Get sale price in requested currency
            $salePrice = $item->getDefaultSalePriceInCurrency($currency, $date);
            
            $priceData[$currency] = [
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'is_base_currency' => ($currency === $baseCurrency)
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'item_id' => $item->item_id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'base_currency' => $baseCurrency,
                'prices' => $priceData
            ]
        ]);
    }
}