<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SOLine;
use App\Models\Sales\SalesQuotation;
use App\Models\Sales\SalesQuotationLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    /**
     * Display a listing of the sales orders.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = SalesOrder::with(['customer', 'salesQuotation'])->get();
        return response()->json(['data' => $orders], 200);
    }

    /**
     * Store a newly created sales order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'so_number' => 'required|unique:SalesOrder,so_number',
            'so_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'quotation_id' => 'nullable|exists:SalesQuotation,quotation_id',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'expected_delivery' => 'nullable|date',
            'status' => 'required|string|max:50',
            'lines' => 'required|array',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.uom_id' => 'required|exists:unit_of_measures,uom_id',
            'lines.*.discount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $taxAmount = 0;

            // Create sales order
            $salesOrder = SalesOrder::create([
                'so_number' => $request->so_number,
                'so_date' => $request->so_date,
                'customer_id' => $request->customer_id,
                'quotation_id' => $request->quotation_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => $request->status,
                'total_amount' => 0, // Will be updated later
                'tax_amount' => 0    // Will be updated later
            ]);

            // Create sales order lines
            foreach ($request->lines as $line) {
                $subtotal = $line['unit_price'] * $line['quantity'];
                $discount = isset($line['discount']) ? $line['discount'] : 0;
                $tax = isset($line['tax']) ? $line['tax'] : 0;
                $total = $subtotal - $discount + $tax;

                SOLine::create([
                    'so_id' => $salesOrder->so_id,
                    'item_id' => $line['item_id'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'discount' => $discount,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total
                ]);

                $totalAmount += $total;
                $taxAmount += $tax;
            }

            // Update totals
            $salesOrder->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount
            ]);

            // If created from quotation, update quotation status
            if ($request->quotation_id) {
                $quotation = SalesQuotation::find($request->quotation_id);
                $quotation->update(['status' => 'Converted']);
            }

            DB::commit();

            return response()->json([
                'data' => $salesOrder->load('salesOrderLines'),
                'message' => 'Sales order created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a sales order from an existing quotation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createFromQuotation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quotation_id' => 'required|exists:SalesQuotation,quotation_id',
            'so_number' => 'required|unique:SalesOrder,so_number',
            'so_date' => 'required|date',
            'expected_delivery' => 'nullable|date',
            'status' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the quotation
            $quotation = SalesQuotation::with('salesQuotationLines')->find($request->quotation_id);

            if ($quotation->status === 'Converted') {
                return response()->json(['message' => 'This quotation has already been converted to a sales order'], 400);
            }

            $totalAmount = 0;
            $taxAmount = 0;

            // Create sales order
            $salesOrder = SalesOrder::create([
                'so_number' => $request->so_number,
                'so_date' => $request->so_date,
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->quotation_id,
                'payment_terms' => $quotation->payment_terms,
                'delivery_terms' => $quotation->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => $request->status,
                'total_amount' => 0, // Will be updated later
                'tax_amount' => 0    // Will be updated later
            ]);

            // Create sales order lines from quotation lines
            foreach ($quotation->salesQuotationLines as $quotationLine) {
                SOLine::create([
                    'so_id' => $salesOrder->so_id,
                    'item_id' => $quotationLine->item_id,
                    'unit_price' => $quotationLine->unit_price,
                    'quantity' => $quotationLine->quantity,
                    'uom_id' => $quotationLine->uom_id,
                    'discount' => $quotationLine->discount,
                    'subtotal' => $quotationLine->subtotal,
                    'tax' => $quotationLine->tax,
                    'total' => $quotationLine->total
                ]);

                $totalAmount += $quotationLine->total;
                $taxAmount += $quotationLine->tax;
            }

            // Update totals
            $salesOrder->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount
            ]);

            // Update quotation status
            $quotation->update(['status' => 'Converted']);

            DB::commit();

            return response()->json([
                'data' => $salesOrder->load('salesOrderLines'),
                'message' => 'Sales order created from quotation successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales order from quotation', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified sales order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = SalesOrder::with([
            'customer',
            'salesQuotation',
            'salesOrderLines.item',
            'salesOrderLines.unitOfMeasure',
            'deliveries',
            'salesInvoices'
        ])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        return response()->json(['data' => $order], 200);
    }

    /**
     * Update the specified sales order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be updated (not delivered or invoiced)
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $order->status . ' sales order'], 400);
        }

        $validator = Validator::make($request->all(), [
            'so_number' => 'required|unique:SalesOrder,so_number,' . $id . ',so_id',
            'so_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'expected_delivery' => 'nullable|date',
            'status' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update($request->all());
        return response()->json(['data' => $order, 'message' => 'Sales order updated successfully'], 200);
    }

    /**
     * Remove the specified sales order from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be deleted (no deliveries or invoices)
        if ($order->deliveries->count() > 0) {
            return response()->json(['message' => 'Cannot delete order with related deliveries'], 400);
        }

        if ($order->salesInvoices->count() > 0) {
            return response()->json(['message' => 'Cannot delete order with related invoices'], 400);
        }

        try {
            DB::beginTransaction();

            // Delete related order lines
            $order->salesOrderLines()->delete();

            // Delete the order
            $order->delete();

            DB::commit();

            return response()->json(['message' => 'Sales order deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete sales order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add a new line to the specified sales order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addLine(Request $request, $id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be updated (not delivered or invoiced)
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $order->status . ' sales order'], 400);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'uom_id' => 'required|exists:unit_of_measures,uom_id',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $subtotal = $request->unit_price * $request->quantity;
            $discount = $request->discount ?? 0;
            $tax = $request->tax ?? 0;
            $total = $subtotal - $discount + $tax;

            $line = SOLine::create([
                'so_id' => $id,
                'item_id' => $request->item_id,
                'unit_price' => $request->unit_price,
                'quantity' => $request->quantity,
                'uom_id' => $request->uom_id,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total
            ]);

            // Update order totals
            $order->total_amount += $total;
            $order->tax_amount += $tax;
            $order->save();

            DB::commit();

            return response()->json(['data' => $line, 'message' => 'Line added to sales order successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add line to sales order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a line in the specified sales order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  int  $lineId
     * @return \Illuminate\Http\Response
     */
    public function updateLine(Request $request, $id, $lineId)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be updated (not delivered or invoiced)
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $order->status . ' sales order'], 400);
        }

        $line = SOLine::where('so_id', $id)->where('line_id', $lineId)->first();

        if (!$line) {
            return response()->json(['message' => 'Order line not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'uom_id' => 'required|exists:unit_of_measures,uom_id',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate new values
            $subtotal = $request->unit_price * $request->quantity;
            $discount = $request->discount ?? 0;
            $tax = $request->tax ?? 0;
            $total = $subtotal - $discount + $tax;

            // Update order totals
            $order->total_amount = $order->total_amount - $line->total + $total;
            $order->tax_amount = $order->tax_amount - $line->tax + $tax;
            $order->save();

            // Update line
            $line->update([
                'item_id' => $request->item_id,
                'unit_price' => $request->unit_price,
                'quantity' => $request->quantity,
                'uom_id' => $request->uom_id,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total
            ]);

            DB::commit();

            return response()->json(['data' => $line, 'message' => 'Order line updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update order line', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove a line from the specified sales order.
     *
     * @param  int  $id
     * @param  int  $lineId
     * @return \Illuminate\Http\Response
     */
    public function removeLine($id, $lineId)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be updated (not delivered or invoiced)
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $order->status . ' sales order'], 400);
        }

        $line = SOLine::where('so_id', $id)->where('line_id', $lineId)->first();

        if (!$line) {
            return response()->json(['message' => 'Order line not found'], 404);
        }

        try {
            DB::beginTransaction();

            // Update order totals
            $order->total_amount -= $line->total;
            $order->tax_amount -= $line->tax;
            $order->save();

            // Delete the line
            $line->delete();

            DB::commit();

            return response()->json(['message' => 'Order line removed successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to remove order line', 'error' => $e->getMessage()], 500);
        }
    }
}