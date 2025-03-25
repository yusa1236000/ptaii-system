<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorContractRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        $rules = [
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_type' => 'nullable|string|max:50',
            'status' => 'nullable|in:draft,active,expired,terminated'
        ];
        
        // Add contract_number validation only for new contracts
        if ($this->isMethod('post')) {
            $rules['contract_number'] = 'required|string|max:50|unique:vendor_contracts,contract_number';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['contract_number'] = 'required|string|max:50|unique:vendor_contracts,contract_number,' . $this->route('vendorContract')->contract_id . ',contract_id';
        }
        
        return $rules;
    }
}