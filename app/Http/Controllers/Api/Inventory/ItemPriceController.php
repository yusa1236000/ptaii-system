<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ItemPriceController extends Controller
{
    /**
     * Display a listing of item prices.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $itemId
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $itemId)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
        
        $query = ItemPrice::where('item_id', $itemId);
        
        // Filter by price type
        if ($request->has('price_type')) {
            $query->where('price_type', $request->price_type);
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Filter by valid date range
        if ($request->has('current_only') && $request->current_only) {
            $query->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            });
        }
        
        $prices = $query->with(['customer', 'vendor'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $prices
        ]);
    }

    /**
     * Store a newly created item price in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $itemId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $itemId)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'price_type' => 'required|string|in:purchase,sale',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3', // New validation for currency
            'min_quantity' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'customer_id' => 'nullable|exists:Customer,customer_id',
            'vendor_id' => 'nullable|exists:Vendor,vendor_id',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Validate that customer_id is only provided for sale prices
        if ($request->price_type === 'purchase' && $request->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Customer ID cannot be specified for purchase prices'
            ], 422);
        }
        
        // Validate that vendor_id is only provided for purchase prices
        if ($request->price_type === 'sale' && $request->vendor_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor ID cannot be specified for sale prices'
            ], 422);
        }

        // Get base currency (from configuration or default)
        $baseCurrency = config('app.base_currency', 'USD');
        
        // Calculate base currency price
        $baseCurrencyPrice = $request->price;
        
        // If the price is not in base currency, convert it
        if ($request->currency_code !== $baseCurrency) {
            // Get the exchange rate for the current date
            $rate = CurrencyRate::where('from_currency', $request->currency_code)
                ->where('to_currency', $baseCurrency)
                ->where('is_active', true)
                ->where('effective_date', '<=', now())
                ->where(function($query) {
                    $query->where('end_date', '>=', now())
                          ->orWhereNull('end_date');
                })
                ->orderBy('effective_date', 'desc')
                ->first();
                
            if (!$rate) {
                // Try to find a reverse rate
                $reverseRate = CurrencyRate::where('from_currency', $baseCurrency)
                    ->where('to_currency', $request->currency_code)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', now())
                    ->where(function($query) {
                        $query->where('end_date', '>=', now())
                              ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();
                    
                if ($reverseRate) {
                    $exchangeRate = 1 / $reverseRate->rate;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No exchange rate found for the specified currency'
                    ], 422);
                }
            } else {
                $exchangeRate = $rate->rate;
            }
            
            // Convert to base currency
            $baseCurrencyPrice = $request->price * $exchangeRate;
        }
        $data = $validator->validated();
        $data['item_id'] = $itemId;
        $data['base_currency_price'] = $baseCurrencyPrice;
        $data['base_currency'] = $baseCurrency;
        
        $price = ItemPrice::create($data);

        // If this is the first price of this type, also update the default price on the item
        if ($price->price_type === 'purchase' && !$item->cost_price) {
            $item->cost_price = $baseCurrencyPrice;
            $item->cost_price_currency = $baseCurrency;
            $item->save();
        }
        
        if ($price->price_type === 'sale' && !$item->sale_price) {
            $item->sale_price = $baseCurrencyPrice;
            $item->sale_price_currency = $baseCurrency;
            $item->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Item price created successfully',
            'data' => $price
        ], 201);
    }

    /**
     * Display the specified item price.
     *
     * @param  int  $itemId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($itemId, $id)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
        
        $price = ItemPrice::with(['customer', 'vendor'])
            ->where('item_id', $itemId)
            ->where('price_id', $id)
            ->first();
            
        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'Item price not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $price
        ]);
    }

    /**
     * Update the specified item price in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $itemId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $itemId, $id)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
        
        $price = ItemPrice::where('item_id', $itemId)
            ->where('price_id', $id)
            ->first();
            
        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'Item price not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'min_quantity' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $price->update($validator->validated());

        // If this is the default price, also update the item's default price
        if ($price->price_type === 'purchase' && $price->price != $item->cost_price && !$price->customer_id && !$price->vendor_id && $price->min_quantity == 1) {
            $item->cost_price = $price->price;
            $item->save();
        }
        
        if ($price->price_type === 'sale' && $price->price != $item->sale_price && !$price->customer_id && !$price->vendor_id && $price->min_quantity == 1) {
            $item->sale_price = $price->price;
            $item->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Item price updated successfully',
            'data' => $price
        ]);
    }

    /**
     * Remove the specified item price from storage.
     *
     * @param  int  $itemId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($itemId, $id)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
        
        $price = ItemPrice::where('item_id', $itemId)
            ->where('price_id', $id)
            ->first();
            
        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'Item price not found'
            ], 404);
        }

        $price->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item price deleted successfully'
        ]);
    }
    
    /**
     * Get the best purchase price for an item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $itemId
     * @return \Illuminate\Http\Response
     */
    public function getBestPurchasePrice(Request $request, $itemId)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
        
        if (!$item->is_purchasable) {
            return response()->json([
                'success' => false,
                'message' => 'Item is not purchasable'
            ], 422);
        }
        
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'nullable|exists:Vendor,vendor_id',
            'quantity' => 'nullable|numeric|min:1',
            'currency_code' => 'nullable|string|size:3' // Allow requesting price in a specific currency
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $vendorId = $request->vendor_id;
        $quantity = $request->quantity ?? 1;
        $currencyCode = $request->currency_code ?? config('app.base_currency', 'USD');
        
        // Get the price in base currency
        $priceInBaseCurrency = $item->getBestPurchasePrice($vendorId, $quantity);
        $baseCurrency = config('app.base_currency', 'USD');
        
        // If requested currency is not base currency, convert
        $price = $priceInBaseCurrency;
        
        if ($currencyCode !== $baseCurrency) {
            // Get exchange rate for conversion
            $rate = CurrencyRate::where('from_currency', $baseCurrency)
                ->where('to_currency', $currencyCode)
                ->where('is_active', true)
                ->where('effective_date', '<=', now())
                ->where(function($query) {
                    $query->where('end_date', '>=', now())
                          ->orWhereNull('end_date');
                })
                ->orderBy('effective_date', 'desc')
                ->first();
                
            if (!$rate) {
                // Try to find a reverse rate
                $reverseRate = CurrencyRate::where('from_currency', $currencyCode)
                    ->where('to_currency', $baseCurrency)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', now())
                    ->where(function($query) {
                        $query->where('end_date', '>=', now())
                              ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();
                    
                if ($reverseRate) {
                    $exchangeRate = 1 / $reverseRate->rate;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No exchange rate found for the specified currency'
                    ], 422);
                }
            } else {
                $exchangeRate = $rate->rate;
            }
            
            // Convert to requested currency
            $price = $priceInBaseCurrency * $exchangeRate;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'item_id' => $item->item_id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'price' => $price,
                'price_in_base_currency' => $priceInBaseCurrency,
                'base_currency' => $baseCurrency,
                'currency' => $currencyCode,
                'quantity' => $quantity,
                'vendor_id' => $vendorId
            ]
        ]);
    }
    
    /**
     * Get the best sale price for an item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $itemId
     * @return \Illuminate\Http\Response
     */
    public function getBestSalePrice(Request $request, $itemId)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
        
        if (!$item->is_sellable) {
            return response()->json([
                'success' => false,
                'message' => 'Item is not sellable'
            ], 422);
        }
        
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:Customer,customer_id',
            'quantity' => 'nullable|numeric|min:1',
            'currency_code' => 'nullable|string|size:3' // Allow requesting price in a specific currency
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $customerId = $request->customer_id;
        $quantity = $request->quantity ?? 1;
        $currencyCode = $request->currency_code;
        
        // If customer is specified and no currency is provided, get customer's preferred currency
        if ($customerId && !$currencyCode) {
            $customer = Customer::find($customerId);
            $currencyCode = $customer->preferred_currency ?? config('app.base_currency', 'USD');
        } else {
            $currencyCode = $currencyCode ?? config('app.base_currency', 'USD');
        }
        
        // Get the price in base currency
        $priceInBaseCurrency = $item->getBestSalePrice($customerId, $quantity);
        $baseCurrency = config('app.base_currency', 'USD');
        
        // If requested currency is not base currency, convert
        $price = $priceInBaseCurrency;
        
        if ($currencyCode !== $baseCurrency) {
            // Get exchange rate for conversion
            $rate = CurrencyRate::where('from_currency', $baseCurrency)
                ->where('to_currency', $currencyCode)
                ->where('is_active', true)
                ->where('effective_date', '<=', now())
                ->where(function($query) {
                    $query->where('end_date', '>=', now())
                          ->orWhereNull('end_date');
                })
                ->orderBy('effective_date', 'desc')
                ->first();
                
            if (!$rate) {
                // Try to find a reverse rate
                $reverseRate = CurrencyRate::where('from_currency', $currencyCode)
                    ->where('to_currency', $baseCurrency)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', now())
                    ->where(function($query) {
                        $query->where('end_date', '>=', now())
                              ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();
                    
                if ($reverseRate) {
                    $exchangeRate = 1 / $reverseRate->rate;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No exchange rate found for the specified currency'
                    ], 422);
                }
            } else {
                $exchangeRate = $rate->rate;
            }
            
            // Convert to requested currency
            $price = $priceInBaseCurrency * $exchangeRate;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'item_id' => $item->item_id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'price' => $price,
                'price_in_base_currency' => $priceInBaseCurrency,
                'base_currency' => $baseCurrency,
                'currency' => $currencyCode,
                'quantity' => $quantity,
                'customer_id' => $customerId
            ]
        ]);
    }
    
    /**
     * Update the item default prices.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $itemId
     * @return \Illuminate\Http\Response
     */
    public function updateDefaultPrices(Request $request, $itemId)
    {
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'is_purchasable' => 'nullable|boolean',
            'is_sellable' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = [];
        
        if ($request->has('cost_price')) {
            $data['cost_price'] = $request->cost_price;
        }
        
        if ($request->has('sale_price')) {
            $data['sale_price'] = $request->sale_price;
        }
        
        if ($request->has('is_purchasable')) {
            $data['is_purchasable'] = $request->is_purchasable;
        }
        
        if ($request->has('is_sellable')) {
            $data['is_sellable'] = $request->is_sellable;
        }
        
        $item->update($data);
        
        return response()->json([
            'success' => true,
            'message' => 'Item default prices updated successfully',
            'data' => $item
        ]);
    }
}