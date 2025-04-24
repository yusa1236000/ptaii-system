<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'status' => 'nullable|in:active,inactive,blacklisted'
        ];
        
        // Add vendor_code validation only for new vendors
        if ($this->isMethod('post')) {
            $rules['vendor_code'] = 'required|string|max:50|unique:vendors,vendor_code';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['vendor_code'] = 'required|string|max:50|unique:vendors,vendor_code,' . $this->route('vendor')->vendor_id . ',vendor_id';
        }
        
        return $rules;
    }
}