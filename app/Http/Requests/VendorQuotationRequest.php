<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorQuotationRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        return [
            'rfq_id' => 'required|exists:request_for_quotations,rfq_id',
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'quotation_date' => 'required|date',
            'validity_date' => 'nullable|date|after_or_equal:quotation_date',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.uom_id' => 'required|exists:unit_of_measures,uom_id',
            'lines.*.delivery_date' => 'nullable|date'
        ];
    }
}