<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoodsReceiptRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        return [
            'receipt_date' => 'required|date',
            'po_id' => 'required|exists:purchase_orders,po_id',
            'lines' => 'required|array|min:1',
            'lines.*.po_line_id' => 'required|exists:po_lines,line_id',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.received_quantity' => 'required|numeric|min:0.01',
            'lines.*.warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'lines.*.location_id' => 'nullable|exists:warehouse_locations,location_id',
            'lines.*.batch_number' => 'nullable|string|max:50'
        ];
    }
}