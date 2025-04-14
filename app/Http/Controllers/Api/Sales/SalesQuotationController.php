<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesQuotation;
use App\Models\Sales\SalesQuotationLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesQuotationController extends Controller
{
    /**
     * Display a listing of the sales quotations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $quotations = SalesQuotation::with('customer')->get();
        return response()->json(['data' => $quotations], 200);
    }

    /**
     * Store a newly created sales quotation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quotation_number' => 'required|unique:SalesQuotation,quotation_number',
            'quotation_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'validity_date' => 'required|date',
            'status' => 'required|string|max:50',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
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

            // Create quotation
            $quotation = SalesQuotation::create([
                'quotation_number' => $request->quotation_number,
                'quotation_date' => $request->quotation_date,
                'customer_id' => $request->customer_id,
                'validity_date' => $request->validity_date,
                'status' => $request->status,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms
            ]);

            // Create quotation lines
            foreach ($request->lines as $line) {
                $subtotal = $line['unit_price'] * $line['quantity'];
                $discount = isset($line['discount']) ? $line['discount'] : 0;
                $tax = isset($line['tax']) ? $line['tax'] : 0;
                $total = $subtotal - $discount + $tax;

                SalesQuotationLine::create([
                    'quotation_id' => $quotation->quotation_id,
                    'item_id' => $line['item_id'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'discount' => $discount,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total
                ]);
            }

            DB::commit();

            return response()->json([
                'data' => $quotation->load('salesQuotationLines'),
                'message' => 'Sales quotation created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales quotation', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified sales quotation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $quotation = SalesQuotation::with(['customer', 'salesQuotationLines.item', 'salesQuotationLines.unitOfMeasure'])->find($id);

        if (!$quotation) {
            return response()->json(['message' => 'Sales quotation not found'], 404);
        }

        return response()->json(['data' => $quotation], 200);
    }

    /**
     * Update the specified sales quotation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $quotation = SalesQuotation::find($id);

        if (!$quotation) {
            return response()->json(['message' => 'Sales quotation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'quotation_number' => 'required|unique:SalesQuotation,quotation_number,' . $id . ',quotation_id',
            'quotation_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'validity_date' => 'required|date',
            'status' => 'required|string|max:50',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $quotation->update($request->all());
        return response()->json(['data' => $quotation, 'message' => 'Sales quotation updated successfully'], 200);
    }

    /**
     * Remove the specified sales quotation from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $quotation = SalesQuotation::find($id);

        if (!$quotation) {
            return response()->json(['message' => 'Sales quotation not found'], 404);
        }

        // Check if the quotation has related sales orders
        if ($quotation->salesOrders->count() > 0) {
            return response()->json(['message' => 'Cannot delete quotation with related sales orders'], 400);
        }

        try {
            DB::beginTransaction();

            // Delete related quotation lines
            $quotation->salesQuotationLines()->delete();

            // Delete the quotation
            $quotation->delete();

            DB::commit();

            return response()->json(['message' => 'Sales quotation deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete sales quotation', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add a new line to the specified sales quotation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addLine(Request $request, $id)
    {
        $quotation = SalesQuotation::find($id);

        if (!$quotation) {
            return response()->json(['message' => 'Sales quotation not found'], 404);
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

        $subtotal = $request->unit_price * $request->quantity;
        $discount = $request->discount ?? 0;
        $tax = $request->tax ?? 0;
        $total = $subtotal - $discount + $tax;

        $line = SalesQuotationLine::create([
            'quotation_id' => $id,
            'item_id' => $request->item_id,
            'unit_price' => $request->unit_price,
            'quantity' => $request->quantity,
            'uom_id' => $request->uom_id,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total
        ]);

        return response()->json(['data' => $line, 'message' => 'Line added to sales quotation successfully'], 201);
    }

    /**
     * Update a line in the specified sales quotation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  int  $lineId
     * @return \Illuminate\Http\Response
     */
    public function updateLine(Request $request, $id, $lineId)
    {
        $quotation = SalesQuotation::find($id);

        if (!$quotation) {
            return response()->json(['message' => 'Sales quotation not found'], 404);
        }

        $line = SalesQuotationLine::where('quotation_id', $id)->where('line_id', $lineId)->first();

        if (!$line) {
            return response()->json(['message' => 'Quotation line not found'], 404);
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

        $subtotal = $request->unit_price * $request->quantity;
        $discount = $request->discount ?? 0;
        $tax = $request->tax ?? 0;
        $total = $subtotal - $discount + $tax;

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

        return response()->json(['data' => $line, 'message' => 'Quotation line updated successfully'], 200);
    }

    /**
     * Remove a line from the specified sales quotation.
     *
     * @param  int  $id
     * @param  int  $lineId
     * @return \Illuminate\Http\Response
     */
    public function removeLine($id, $lineId)
    {
        $quotation = SalesQuotation::find($id);

        if (!$quotation) {
            return response()->json(['message' => 'Sales quotation not found'], 404);
        }

        $line = SalesQuotationLine::where('quotation_id', $id)->where('line_id', $lineId)->first();

        if (!$line) {
            return response()->json(['message' => 'Quotation line not found'], 404);
        }

        $line->delete();

        return response()->json(['message' => 'Quotation line removed successfully'], 200);
    }
}