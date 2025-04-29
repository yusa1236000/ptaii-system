<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\CurrencyRate;
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
        // Add preferred_currency field with default if not provided
        if (!$request->has('preferred_currency')) {
            $request->merge(['preferred_currency' => config('app.base_currency', 'USD')]);
        }
        
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
    
    /**
     * Get vendor transactions in specified currency.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Vendor  $vendor
     * @return \Illuminate\Http\Response
     */
    public function getTransactionsInCurrency(Request $request, Vendor $vendor)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'currency' => 'required|string|size:3',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $currency = $request->currency;
        $date = $request->date ?? now()->format('Y-m-d');
        
        // Get purchase orders
        $purchaseOrders = $vendor->purchaseOrders()
            ->with(['lines'])
            ->get()
            ->map(function($po) use ($currency, $date) {
                $amounts = $po->getAmountsInCurrency($currency, $date);
                return [
                    'po_id' => $po->po_id,
                    'po_number' => $po->po_number,
                    'po_date' => $po->po_date,
                    'original_currency' => $po->currency_code,
                    'display_currency' => $currency,
                    'total_amount' => $amounts['total_amount'],
                    'tax_amount' => $amounts['tax_amount']
                ];
            });
        
        // Get invoices
        $invoices = $vendor->invoices()
            ->with(['lines'])
            ->get()
            ->map(function($invoice) use ($currency, $date) {
                $amounts = $invoice->getAmountsInCurrency($currency, $date);
                return [
                    'invoice_id' => $invoice->invoice_id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'original_currency' => $invoice->currency_code,
                    'display_currency' => $currency,
                    'total_amount' => $amounts['total_amount'],
                    'tax_amount' => $amounts['tax_amount']
                ];
            });
        
        // Get payables
        $payables = $vendor->payables()
            ->get()
            ->map(function($payable) use ($currency, $date) {
                $amounts = $payable->getAmountsInCurrency($currency, $date);
                return [
                    'payable_id' => $payable->payable_id,
                    'invoice_number' => $payable->vendorInvoice->invoice_number,
                    'due_date' => $payable->due_date,
                    'original_currency' => $payable->currency_code,
                    'display_currency' => $currency,
                    'amount' => $amounts['amount'],
                    'paid_amount' => $amounts['paid_amount'],
                    'balance' => $amounts['balance']
                ];
            });
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'vendor' => $vendor,
                'currency' => $currency,
                'purchase_orders' => $purchaseOrders,
                'invoices' => $invoices,
                'payables' => $payables
            ]
        ]);
    }
    
    /**
     * Update vendor's preferred currency.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Vendor  $vendor
     * @return \Illuminate\Http\Response
     */
    public function updatePreferredCurrency(Request $request, Vendor $vendor)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'preferred_currency' => 'required|string|size:3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if currency is valid by checking if exchange rate exists
        $baseCurrency = config('app.base_currency', 'USD');
        if ($request->preferred_currency !== $baseCurrency) {
            $rate = CurrencyRate::where(function($query) use ($request, $baseCurrency) {
                    $query->where('from_currency', $request->preferred_currency)
                          ->where('to_currency', $baseCurrency);
                })
                ->orWhere(function($query) use ($request, $baseCurrency) {
                    $query->where('from_currency', $baseCurrency)
                          ->where('to_currency', $request->preferred_currency);
                })
                ->where('is_active', true)
                ->first();
                
            if (!$rate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No exchange rate found for the specified currency'
                ], 422);
            }
        }
        
        $vendor->update([
            'preferred_currency' => $request->preferred_currency
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor preferred currency updated successfully',
            'data' => $vendor
        ]);
    }
}