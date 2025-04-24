<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Delivery;
use App\Models\Sales\DeliveryLine;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SOLine;
use App\Models\Sales\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the deliveries.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $deliveries = Delivery::with(['customer', 'salesOrder'])->get();
        return response()->json(['data' => $deliveries], 200);
    }

    /**
     * Store a newly created delivery in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_number' => 'required|unique:Delivery,delivery_number',
            'delivery_date' => 'required|date',
            'so_id' => 'required|exists:SalesOrder,so_id',
            'shipping_method' => 'nullable|string|max:50',
            'tracking_number' => 'nullable|string|max:50',
            'lines' => 'required|array',
            'lines.*.so_line_id' => 'required|exists:SOLine,line_id',
            'lines.*.delivered_quantity' => 'required|numeric|min:0',
            'lines.*.warehouse_id' => 'required|exists:Warehouse,warehouse_id',
            'lines.*.location_id' => 'required|exists:WarehouseLocation,location_id',
            'lines.*.batch_number' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the sales order
            $salesOrder = SalesOrder::find($request->so_id);

            // Create delivery
            $delivery = Delivery::create([
                'delivery_number' => $request->delivery_number,
                'delivery_date' => $request->delivery_date,
                'so_id' => $request->so_id,
                'customer_id' => $salesOrder->customer_id,
                'status' => 'Pending',
                'shipping_method' => $request->shipping_method,
                'tracking_number' => $request->tracking_number
            ]);

            // Create delivery lines
            foreach ($request->lines as $line) {
                $soLine = SOLine::find($line['so_line_id']);

                // Validate if the delivered quantity is valid
                if ($line['delivered_quantity'] > $soLine->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Delivered quantity exceeds ordered quantity for item ' . $soLine->item_id
                    ], 400);
                }

                DeliveryLine::create([
                    'delivery_id' => $delivery->delivery_id,
                    'so_line_id' => $line['so_line_id'],
                    'item_id' => $soLine->item_id,
                    'delivered_quantity' => $line['delivered_quantity'],
                    'warehouse_id' => $line['warehouse_id'],
                    'location_id' => $line['location_id'],
                    'batch_number' => $line['batch_number'] ?? null
                ]);

                // Update item stock (assuming there's a stock_transaction table)
                // This would be handled by a separate service or a database trigger
            }

            // Update sales order status
            $salesOrder->update(['status' => 'Delivering']);

            DB::commit();

            return response()->json([
                'data' => $delivery->load('deliveryLines'),
                'message' => 'Delivery created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create delivery', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified delivery.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $delivery = Delivery::with([
            'customer',
            'salesOrder',
            'deliveryLines.item',
            'deliveryLines.warehouse',
            'deliveryLines.warehouseLocation',
            'deliveryLines.salesOrderLine'
        ])->find($id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        return response()->json(['data' => $delivery], 200);
    }

    /**
     * Update the specified delivery in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        // Check if delivery can be updated (not completed)
        if ($delivery->status === 'Completed') {
            return response()->json(['message' => 'Cannot update a completed delivery'], 400);
        }

        $validator = Validator::make($request->all(), [
            'delivery_number' => 'required|unique:Delivery,delivery_number,' . $id . ',delivery_id',
            'delivery_date' => 'required|date',
            'shipping_method' => 'nullable|string|max:50',
            'tracking_number' => 'nullable|string|max:50',
            'status' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $delivery->update($request->all());

            // If status is changed to 'Completed', update the sales order status
            if ($request->status === 'Completed' && $delivery->status !== 'Completed') {
                $salesOrder = SalesOrder::find($delivery->so_id);

                // Check if all ordered items have been fully delivered
                $allDelivered = true;
                foreach ($salesOrder->salesOrderLines as $soLine) {
                    $deliveredQuantity = DeliveryLine::where('so_line_id', $soLine->line_id)
                        ->where('delivery_id', $delivery->delivery_id)
                        ->sum('delivered_quantity');

                    if ($deliveredQuantity < $soLine->quantity) {
                        $allDelivered = false;
                        break;
                    }
                }

                if ($allDelivered) {
                    $salesOrder->update(['status' => 'Delivered']);
                }
            }

            DB::commit();

            return response()->json(['data' => $delivery, 'message' => 'Delivery updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update delivery', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified delivery from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        // Check if delivery can be deleted (not completed)
        if ($delivery->status === 'Completed') {
            return response()->json(['message' => 'Cannot delete a completed delivery'], 400);
        }

        try {
            DB::beginTransaction();

            // Delete related delivery lines
            $delivery->deliveryLines()->delete();

            // Delete the delivery
            $delivery->delete();

            // Update sales order status if needed
            $salesOrder = SalesOrder::find($delivery->so_id);
            $remainingDeliveries = Delivery::where('so_id', $delivery->so_id)->count();

            if ($remainingDeliveries === 0) {
                $salesOrder->update(['status' => 'Confirmed']);
            }

            DB::commit();

            return response()->json(['message' => 'Delivery deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete delivery', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark a delivery as completed and update inventory.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function complete($id)
    {
        $delivery = Delivery::with('deliveryLines')->find($id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        if ($delivery->status === 'Completed') {
            return response()->json(['message' => 'Delivery already completed'], 400);
        }

        try {
            DB::beginTransaction();

            // Update inventory for each delivery line
            foreach ($delivery->deliveryLines as $line) {
                $item = Item::find($line->item_id);

                // Update item stock
                $item->current_stock -= $line->delivered_quantity;
                $item->save();

                // Create stock transaction record (if applicable)
                // This would typically be handled by a separate service or a database trigger
            }

            // Update delivery status
            $delivery->status = 'Completed';
            $delivery->save();

            // Update sales order status
            $salesOrder = SalesOrder::find($delivery->so_id);

            // Check if all ordered items have been fully delivered
            $allDelivered = true;
            foreach ($salesOrder->salesOrderLines as $soLine) {
                $deliveredQuantity = DeliveryLine::join('Delivery', 'DeliveryLine.delivery_id', '=', 'Delivery.delivery_id')
                    ->where('DeliveryLine.so_line_id', $soLine->line_id)
                    ->where('Delivery.status', 'Completed')
                    ->sum('DeliveryLine.delivered_quantity');

                if ($deliveredQuantity < $soLine->quantity) {
                    $allDelivered = false;
                    break;
                }
            }

            if ($allDelivered) {
                $salesOrder->update(['status' => 'Delivered']);
            }

            DB::commit();

            return response()->json(['message' => 'Delivery completed successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to complete delivery', 'error' => $e->getMessage()], 500);
        }
    }
}
