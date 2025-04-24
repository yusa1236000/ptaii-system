<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorEvaluationRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        return [
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'evaluation_date' => 'required|date',
            'quality_score' => 'required|numeric|min:0|max:10',
            'delivery_score' => 'required|numeric|min:0|max:10',
            'price_score' => 'required|numeric|min:0|max:10',
            'service_score' => 'required|numeric|min:0|max:10'
        ];
    }
}