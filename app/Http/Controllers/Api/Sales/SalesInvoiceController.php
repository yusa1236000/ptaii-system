<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SOLine;
use App\Models\Accounting\CustomerReceivable;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    /**
     * Display a listing of the sales invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $invoices = SalesInvoice::with(['customer', 'salesOrder'])->get();
        return response()->json(['data' => $invoices], 200);
    }

    /**
     * Store a newly created sales invoice in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => 'required|unique:SalesInvoice,invoice_number',
            'invoice_date' => 'required|date',
            'so_id' => 'required|exists:SalesOrder,so_id',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => 'required|string|max:50',
            'currency_code' => 'nullable|string|size:3', // New field for currency
            'lines' => 'required|array',
            'lines.*.so_line_id' => 'required|exists:SOLine,line_id',
            'lines.*.quantity' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the sales order
            $salesOrder = SalesOrder::find($request->so_id);
            
            // Determine currency (from request, sales order, or default)
            $currencyCode = $request->currency_code ?? $salesOrder->currency_code ?? config('app.base_currency', 'USD');
            $baseCurrency = config('app.base_currency', 'USD');
            
            // Get exchange rate if invoice currency is different from base currency
            $exchangeRate = 1.0; // Default for base currency
            
            if ($currencyCode !== $baseCurrency) {
                $rate = CurrencyRate::where('from_currency', $currencyCode)
                    ->where('to_currency', $baseCurrency)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', $request->invoice_date)
                    ->where(function($query) use ($request) {
                        $query->where('end_date', '>=', $request->invoice_date)
                              ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();
                    
                if (!$rate) {
                    // Try to find a reverse rate
                    $reverseRate = CurrencyRate::where('from_currency', $baseCurrency)
                        ->where('to_currency', $currencyCode)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $request->invoice_date)
                        ->where(function($query) use ($request) {
                            $query->where('end_date', '>=', $request->invoice_date)
                                  ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();
                        
                    if ($reverseRate) {
                        $exchangeRate = 1 / $reverseRate->rate;
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'No exchange rate found for the specified currency on the invoice date'
                        ], 422);
                    }
                } else {
                    $exchangeRate = $rate->rate;
                }
            }
            
            $totalAmount = 0;
            $taxAmount = 0;

            // Create sales invoice
            $invoice = SalesInvoice::create([
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'customer_id' => $salesOrder->customer_id,
                'so_id' => $request->so_id,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'total_amount' => 0, // Will be updated later
                'tax_amount' => 0,   // Will be updated later
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency' => $baseCurrency,
                'base_currency_total' => 0, // Will be updated later
                'base_currency_tax' => 0    // Will be updated later
            ]);

            // Create invoice lines
            foreach ($request->lines as $line) {
                $soLine = SOLine::find($line['so_line_id']);
                
                // Validate if the invoiced quantity is valid
                if ($line['quantity'] > $soLine->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Invoiced quantity exceeds ordered quantity for item ' . $soLine->item_id
                    ], 400);
                }
                
                // Handle currency conversion for line items if needed
                $unitPrice = $soLine->unit_price;
                
                // If invoice currency differs from sales order currency
                if ($currencyCode !== $salesOrder->currency_code) {
                    // Convert from sales order currency to invoice currency via base currency
                    // First get price in base currency
                    $baseUnitPrice = $soLine->base_currency_unit_price;
                    
                    // Then convert to invoice currency
                    if ($currencyCode !== $baseCurrency) {
                        $unitPrice = $baseUnitPrice / $exchangeRate;
                    } else {
                        $unitPrice = $baseUnitPrice; // Already in base currency
                    }
                }
                
                // Calculate amounts
                $subtotal = $unitPrice * $line['quantity'];
                $discount = ($soLine->discount / $soLine->quantity) * $line['quantity'];
                $tax = ($soLine->tax / $soLine->quantity) * $line['quantity'];
                $total = $subtotal - $discount + $tax;
                
                // Calculate base currency amounts
                $baseUnitPrice = $unitPrice * $exchangeRate;
                $baseSubtotal = $subtotal * $exchangeRate;
                $baseDiscount = $discount * $exchangeRate;
                $baseTax = $tax * $exchangeRate;
                $baseTotal = $total * $exchangeRate;
                
                SalesInvoiceLine::create([
                    'invoice_id' => $invoice->invoice_id,
                    'so_line_id' => $line['so_line_id'],
                    'item_id' => $soLine->item_id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'base_currency_unit_price' => $baseUnitPrice,
                    'base_currency_subtotal' => $baseSubtotal,
                    'base_currency_discount' => $baseDiscount,
                    'base_currency_tax' => $baseTax,
                    'base_currency_total' => $baseTotal
                ]);
                
                $totalAmount += $total;
                $taxAmount += $tax;
            }

            // Update invoice totals
            $baseCurrencyTotal = $totalAmount * $exchangeRate;
            $baseCurrencyTax = $taxAmount * $exchangeRate;
            
            $invoice->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'base_currency_total' => $baseCurrencyTotal,
                'base_currency_tax' => $baseCurrencyTax
            ]);

            // Create customer receivable record
            CustomerReceivable::create([
                'customer_id' => $salesOrder->customer_id,
                'invoice_id' => $invoice->invoice_id,
                'amount' => $totalAmount,
                'due_date' => $request->due_date,
                'paid_amount' => 0,
                'balance' => $totalAmount,
                'status' => 'Open',
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency' => $baseCurrency,
                'base_currency_amount' => $baseCurrencyTotal,
                'base_currency_balance' => $baseCurrencyTotal
            ]);

            // Update sales order status
            $salesOrder->update(['status' => 'Invoiced']);

            DB::commit();
            
            return response()->json([
                'data' => $invoice->load('salesInvoiceLines'), 
                'message' => 'Sales invoice created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales invoice', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a sales invoice from an existing sales order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createFromOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'so_id' => 'required|exists:SalesOrder,so_id',
            'invoice_number' => 'required|unique:SalesInvoice,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the sales order with its lines
            $salesOrder = SalesOrder::with('salesOrderLines')->find($request->so_id);

            // Check if order is in a valid state for invoicing
            if (!in_array($salesOrder->status, ['Confirmed', 'Delivered'])) {
                return response()->json(['message' => 'Sales order must be confirmed or delivered to create an invoice'], 400);
            }

            // Create sales invoice
            $invoice = SalesInvoice::create([
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'customer_id' => $salesOrder->customer_id,
                'so_id' => $salesOrder->so_id,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'total_amount' => $salesOrder->total_amount,
                'tax_amount' => $salesOrder->tax_amount
            ]);

            // Create invoice lines
            foreach ($salesOrder->salesOrderLines as $soLine) {
                SalesInvoiceLine::create([
                    'invoice_id' => $invoice->invoice_id,
                    'so_line_id' => $soLine->line_id,
                    'item_id' => $soLine->item_id,
                    'quantity' => $soLine->quantity,
                    'unit_price' => $soLine->unit_price,
                    'discount' => $soLine->discount,
                    'subtotal' => $soLine->subtotal,
                    'tax' => $soLine->tax,
                    'total' => $soLine->total
                ]);
            }

            // Create customer receivable record
            CustomerReceivable::create([
                'customer_id' => $salesOrder->customer_id,
                'invoice_id' => $invoice->invoice_id,
                'amount' => $salesOrder->total_amount,
                'due_date' => $request->due_date,
                'paid_amount' => 0,
                'balance' => $salesOrder->total_amount,
                'status' => 'Open'
            ]);

            // Update sales order status
            $salesOrder->update(['status' => 'Invoiced']);

            DB::commit();

            return response()->json([
                'data' => $invoice->load('salesInvoiceLines'),
                'message' => 'Sales invoice created from order successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales invoice from order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified sales invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $invoice = SalesInvoice::with([
            'customer',
            'salesOrder',
            'salesInvoiceLines.item',
            'salesInvoiceLines.salesOrderLine',
            'customerReceivables',
            'salesReturns'
        ])->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Sales invoice not found'], 404);
        }

        return response()->json(['data' => $invoice], 200);
    }

    /**
     * Update the specified sales invoice in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $invoice = SalesInvoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Sales invoice not found'], 404);
        }

        // Check if invoice can be updated (not paid)
        if (in_array($invoice->status, ['Paid', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $invoice->status . ' invoice'], 400);
        }

        $validator = Validator::make($request->all(), [
            'invoice_number' => 'required|unique:SalesInvoice,invoice_number,' . $id . ',invoice_id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $invoice->update($request->all());

            // Update receivable if status changes
            if ($request->status === 'Paid' && $invoice->status !== 'Paid') {
                $receivable = CustomerReceivable::where('invoice_id', $invoice->invoice_id)->first();
                if ($receivable) {
                    $receivable->update([
                        'paid_amount' => $receivable->amount,
                        'balance' => 0,
                        'status' => 'Closed'
                    ]);
                }
            }

            DB::commit();

            return response()->json(['data' => $invoice, 'message' => 'Sales invoice updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update sales invoice', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified sales invoice from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $invoice = SalesInvoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Sales invoice not found'], 404);
        }

        // Check if invoice can be deleted (not paid and no returns)
        if (in_array($invoice->status, ['Paid', 'Closed'])) {
            return response()->json(['message' => 'Cannot delete a ' . $invoice->status . ' invoice'], 400);
        }

        if ($invoice->salesReturns->count() > 0) {
            return response()->json(['message' => 'Cannot delete invoice with related returns'], 400);
        }

        try {
            DB::beginTransaction();

            // Delete related receivables
            CustomerReceivable::where('invoice_id', $invoice->invoice_id)->delete();

            // Delete related invoice lines
            $invoice->salesInvoiceLines()->delete();

            // Delete the invoice
            $invoice->delete();

            // Update sales order status if needed
            $salesOrder = SalesOrder::find($invoice->so_id);
            $remainingInvoices = SalesInvoice::where('so_id', $invoice->so_id)->count();

            if ($remainingInvoices === 0) {
                $salesOrder->update(['status' => 'Confirmed']);
            }

            DB::commit();

            return response()->json(['message' => 'Sales invoice deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete sales invoice', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get payment information for a specific invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function paymentInfo($id)
    {
        $invoice = SalesInvoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Sales invoice not found'], 404);
        }

        $receivable = CustomerReceivable::where('invoice_id', $id)->with('receivablePayments')->first();

        if (!$receivable) {
            return response()->json(['message' => 'Receivable information not found'], 404);
        }

        return response()->json(['data' => $receivable, 'message' => 'Payment information retrieved successfully'], 200);
    }
}