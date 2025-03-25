<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        $rules = [
            'invoice_date' => 'required|date',
            'po_id' => 'required|exists:purchase_orders,po_id',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'lines' => 'required|array|min:1',
            'lines.*.po_line_id' => 'required|exists:po_lines,line_id',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax' => 'nullable|numeric|min:0'
        ];
        
        // Add invoice_number validation only for new invoices
        if ($this->isMethod('post')) {
            $rules['invoice_number'] = 'required|string|max:50|unique:vendor_invoices,invoice_number';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['invoice_number'] = 'required|string|max:50|unique:vendor_invoices,invoice_number,' . $this->route('vendorInvoice')->invoice_id . ',invoice_id';
        }
        
        return $rules;
    }
}