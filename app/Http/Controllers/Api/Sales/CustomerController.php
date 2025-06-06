<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Customer;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = Customer::all();
        return response()->json(['data' => $customers], 200);
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|unique:Customer,customer_code',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'preferred_currency' => 'nullable|string|size:3', // Added currency validation
            'status' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If preferred_currency is not provided, set to system default
        if (!$request->has('preferred_currency')) {
            $request->merge(['preferred_currency' => config('app.base_currency', 'USD')]);
        }

        $customer = Customer::create($request->all());
        return response()->json(['data' => $customer, 'message' => 'Customer created successfully'], 201);
    }

    /**
     * Display the specified customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json(['data' => $customer], 200);
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|unique:Customer,customer_code,' . $id . ',customer_id',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'preferred_currency' => 'nullable|string|size:3', // Added currency validation
            'status' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $customer->update($request->all());
        return response()->json(['data' => $customer, 'message' => 'Customer updated successfully'], 200);
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully'], 200);
    }

    /**
     * Get all sales quotations for a specific customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function quotations($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $quotations = $customer->salesQuotations;
        return response()->json(['data' => $quotations], 200);
    }

    /**
     * Get all sales orders for a specific customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function orders($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $orders = $customer->salesOrders;
        return response()->json(['data' => $orders], 200);
    }

    /**
     * Get all invoices for a specific customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function invoices($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $invoices = $customer->salesInvoices;
        return response()->json(['data' => $invoices], 200);
    }
    
    /**
     * Get customer transactions in specified currency.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getTransactionsInCurrency(Request $request, $id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|size:3',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $currency = $request->currency;
        $date = $request->date ?? now()->format('Y-m-d');
        
        // Get orders
        $orders = $customer->salesOrders()
            ->with(['salesOrderLines'])
            ->get()
            ->map(function($order) use ($currency, $date) {
                $amounts = $order->getAmountsInCurrency($currency, $date);
                return [
                    'so_id' => $order->so_id,
                    'so_number' => $order->so_number,
                    'so_date' => $order->so_date,
                    'original_currency' => $order->currency_code,
                    'display_currency' => $currency,
                    'total_amount' => $amounts['total_amount'],
                    'tax_amount' => $amounts['tax_amount']
                ];
            });
        
        // Get invoices
        $invoices = $customer->salesInvoices()
            ->with(['salesInvoiceLines'])
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
        
        // Get receivables
        $receivables = $customer->receivables()
            ->get()
            ->map(function($receivable) use ($currency, $date) {
                $amounts = $receivable->getAmountsInCurrency($currency, $date);
                return [
                    'receivable_id' => $receivable->receivable_id,
                    'invoice_number' => $receivable->salesInvoice->invoice_number,
                    'due_date' => $receivable->due_date,
                    'original_currency' => $receivable->currency_code,
                    'display_currency' => $currency,
                    'amount' => $amounts['amount'],
                    'paid_amount' => $amounts['paid_amount'],
                    'balance' => $amounts['balance']
                ];
            });
        
        return response()->json([
            'data' => [
                'customer' => $customer,
                'currency' => $currency,
                'orders' => $orders,
                'invoices' => $invoices,
                'receivables' => $receivables
            ]
        ], 200);
    }
}