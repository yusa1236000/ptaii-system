<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        return [
            'po_date' => 'required|date',
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'expected_delivery' => 'nullable|date|after_or_equal:po_date',
            'quotation_id' => 'nullable|exists:vendor_quotations,quotation_id',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.uom_id' => 'required|exists:unit_of_measures,uom_id',
            'lines.*.tax' => 'nullable|numeric|min:0'
        ];
    }
}