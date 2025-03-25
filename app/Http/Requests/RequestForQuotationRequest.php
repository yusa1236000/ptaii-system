<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestForQuotationRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        return [
            'rfq_date' => 'required|date',
            'validity_date' => 'nullable|date|after_or_equal:rfq_date',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.uom_id' => 'required|exists:unit_of_measures,uom_id',
            'lines.*.required_date' => 'nullable|date'
        ];
    }
}