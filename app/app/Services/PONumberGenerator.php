<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Carbon\Carbon;

class PONumberGenerator
{
    public function generate()
    {
        $prefix = 'PO';
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        
        // Get the last PO number
        $lastPO = PurchaseOrder::where('po_number', 'like', "{$prefix}{$year}{$month}%")
                                 ->orderBy('po_number', 'desc')
                                 ->first();
        
        if ($lastPO) {
            // Extract the sequence number
            $sequence = (int) substr($lastPO->po_number, -4);
            $sequence++;
        } else {
            $sequence = 1;
        }
        
        // Format the PO number
        $poNumber = $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return $poNumber;
    }
}