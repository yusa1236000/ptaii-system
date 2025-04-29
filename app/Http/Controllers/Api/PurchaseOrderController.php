<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\VendorQuotation;
use App\Http\Requests\PurchaseOrderRequest;
use App\Services\PONumberGenerator;
use App\Models\CurrencyRate;
use App\Models\Vendor;
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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('po_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PurchaseOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Generate PO number
            $poNumber = $this->poNumberGenerator->generate();
            
            // Get the vendor to check for default currency
            $vendor = Vendor::find($request->vendor_id);
            
            // Determine currency to use (from request, vendor preference, or system default)
            $currencyCode = $request->currency_code ?? $vendor->preferred_currency ?? config('app.base_currency', 'USD');
            $baseCurrency = config('app.base_currency', 'USD');
            
            // Get exchange rate
            $exchangeRate = 1.0; // Default for base currency
            
            if ($currencyCode !== $baseCurrency) {
                $rate = CurrencyRate::where('from_currency', $currencyCode)
                    ->where('to_currency', $baseCurrency)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', $request->po_date)
                    ->where(function($query) use ($request) {
                        $query->where('end_date', '>=', $request->po_date)
                              ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();
                    
                if (!$rate) {
                    // Try to find a reverse rate
                    $reverseRate = CurrencyRate::where('from_currency', $baseCurrency)
                        ->where('to_currency', $currencyCode)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $request->po_date)
                        ->where(function($query) use ($request) {
                            $query->where('end_date', '>=', $request->po_date)
                                  ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();
                        
                    if ($reverseRate) {
                        $exchangeRate = 1 / $reverseRate->rate;
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No exchange rate found for the specified currency on the purchase date'
                        ], 422);
                    }
                } else {
                    $exchangeRate = $rate->rate;
                }
            }
            
            // Calculate totals in document currency
            $subtotal = 0;
            $taxTotal = 0;
            
            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }
            
            $totalAmount = $subtotal + $taxTotal;
            
            // Calculate totals in base currency
            $baseCurrencyTotal = $totalAmount * $exchangeRate;
            $baseCurrencyTax = $taxTotal * $exchangeRate;
            
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
                'tax_amount' => $taxTotal,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency_total' => $baseCurrencyTotal,
                'base_currency_tax' => $baseCurrencyTax,
                'base_currency' => $baseCurrency
            ]);
            
            // Create PO lines
            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $lineTotal = $lineSubtotal + $lineTax;
                
                // Calculate base currency amounts
                $baseUnitPrice = $line['unit_price'] * $exchangeRate;
                $baseSubtotal = $lineSubtotal * $exchangeRate;
                $baseTax = $lineTax * $exchangeRate;
                $baseTotal = $lineTotal * $exchangeRate;
                
                $purchaseOrder->lines()->create([
                    'item_id' => $line['item_id'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'subtotal' => $lineSubtotal,
                    'tax' => $lineTax,
                    'total' => $lineTotal,
                    // New multicurrency fields
                    'base_currency_unit_price' => $baseUnitPrice,
                    'base_currency_subtotal' => $baseSubtotal,
                    'base_currency_tax' => $baseTax,
                    'base_currency_total' => $baseTotal
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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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
            
            // Determine currency to use
            $currencyCode = $request->currency_code ?? $purchaseOrder->currency_code;
            $baseCurrency = config('app.base_currency', 'USD');
            
            // Get exchange rate if the currency has changed
            $exchangeRate = $purchaseOrder->exchange_rate;
            
            if ($currencyCode !== $purchaseOrder->currency_code) {
                if ($currencyCode !== $baseCurrency) {
                    $rate = CurrencyRate::where('from_currency', $currencyCode)
                        ->where('to_currency', $baseCurrency)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $request->po_date)
                        ->where(function($query) use ($request) {
                            $query->where('end_date', '>=', $request->po_date)
                                ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();
                        
                    if (!$rate) {
                        // Try to find a reverse rate
                        $reverseRate = CurrencyRate::where('from_currency', $baseCurrency)
                            ->where('to_currency', $currencyCode)
                            ->where('is_active', true)
                            ->where('effective_date', '<=', $request->po_date)
                            ->where(function($query) use ($request) {
                                $query->where('end_date', '>=', $request->po_date)
                                    ->orWhereNull('end_date');
                            })
                            ->orderBy('effective_date', 'desc')
                            ->first();
                            
                        if ($reverseRate) {
                            $exchangeRate = 1 / $reverseRate->rate;
                        } else {
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No exchange rate found for the specified currency on the purchase date'
                            ], 422);
                        }
                    } else {
                        $exchangeRate = $rate->rate;
                    }
                } else {
                    $exchangeRate = 1.0; // Base currency
                }
            }
            
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
            
            // Calculate totals in base currency
            $baseCurrencyTotal = $totalAmount * $exchangeRate;
            $baseCurrencyTax = $taxTotal * $exchangeRate;
            
            // Update purchase order
            $purchaseOrder->update([
                'po_date' => $request->po_date,
                'vendor_id' => $request->vendor_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxTotal,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency_total' => $baseCurrencyTotal,
                'base_currency_tax' => $baseCurrencyTax
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
                    
                    // Calculate base currency amounts
                    $baseUnitPrice = $line['unit_price'] * $exchangeRate;
                    $baseSubtotal = $lineSubtotal * $exchangeRate;
                    $baseTax = $lineTax * $exchangeRate;
                    $baseTotal = $lineTotal * $exchangeRate;
                    
                    $purchaseOrder->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $line['unit_price'],
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'subtotal' => $lineSubtotal,
                        'tax' => $lineTax,
                        'total' => $lineTotal,
                        // New multicurrency fields
                        'base_currency_unit_price' => $baseUnitPrice,
                        'base_currency_subtotal' => $baseSubtotal,
                        'base_currency_tax' => $baseTax,
                        'base_currency_total' => $baseTotal
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
    /**
     * Convert purchase order currency.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function convertCurrency(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Only allow currency conversion for draft orders
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft Purchase Orders can have their currency converted'
            ], 400);
        }
        
        $validator = Validator::make($request->all(), [
            'currency_code' => 'required|string|size:3',
            'use_exchange_rate_date' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Don't convert if already in the target currency
        if ($purchaseOrder->currency_code === $request->currency_code) {
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order is already in the specified currency',
                'data' => $purchaseOrder
            ]);
        }
        
        $baseCurrency = config('app.base_currency', 'USD');
        
        try {
            DB::beginTransaction();
            
            // Determine which exchange rate to use
            $useExchangeRateDate = $request->use_exchange_rate_date ?? true;
            $exchangeRateDate = $useExchangeRateDate ? $purchaseOrder->po_date : now()->format('Y-m-d');
            
            // Get exchange rate from base currency to target currency
            if ($request->currency_code !== $baseCurrency) {
                $rate = CurrencyRate::where('from_currency', $baseCurrency)
                    ->where('to_currency', $request->currency_code)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', $exchangeRateDate)
                    ->where(function($query) use ($exchangeRateDate) {
                        $query->where('end_date', '>=', $exchangeRateDate)
                            ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();
                    
                if (!$rate) {
                    // Try to find a reverse rate
                    $reverseRate = CurrencyRate::where('from_currency', $request->currency_code)
                        ->where('to_currency', $baseCurrency)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $exchangeRateDate)
                        ->where(function($query) use ($exchangeRateDate) {
                            $query->where('end_date', '>=', $exchangeRateDate)
                                ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();
                        
                    if ($reverseRate) {
                        $newExchangeRate = 1 / $reverseRate->rate;
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No exchange rate found for the specified currency'
                        ], 422);
                    }
                } else {
                    $newExchangeRate = $rate->rate;
                }
            } else {
                // Converting to base currency
                $newExchangeRate = 1.0;
            }
            
            // Calculate conversion factor between old and new currencies
            $conversionFactor = $newExchangeRate / $purchaseOrder->exchange_rate;
            
            // Update order totals
            $newTotalAmount = $purchaseOrder->base_currency_total / $newExchangeRate;
            $newTaxAmount = $purchaseOrder->base_currency_tax / $newExchangeRate;
            
            // Update purchase order
            $purchaseOrder->update([
                'currency_code' => $request->currency_code,
                'exchange_rate' => $newExchangeRate,
                'total_amount' => $newTotalAmount,
                'tax_amount' => $newTaxAmount
            ]);
            
            // Update all line items
            foreach ($purchaseOrder->lines as $line) {
                $newUnitPrice = $line->base_currency_unit_price / $newExchangeRate;
                $newSubtotal = $line->base_currency_subtotal / $newExchangeRate;
                $newTax = $line->base_currency_tax / $newExchangeRate;
                $newTotal = $line->base_currency_total / $newExchangeRate;
                
                $line->update([
                    'unit_price' => $newUnitPrice,
                    'subtotal' => $newSubtotal,
                    'tax' => $newTax,
                    'total' => $newTotal
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order currency converted successfully',
                'data' => $purchaseOrder->fresh()->load(['vendor', 'lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert Purchase Order currency',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}