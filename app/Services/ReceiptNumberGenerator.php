<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use Carbon\Carbon;

class ReceiptNumberGenerator
{
    public function generate()
    {
        $prefix = 'GR';
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        
        // Get the last receipt number
        $lastReceipt = GoodsReceipt::where('receipt_number', 'like', "{$prefix}{$year}{$month}%")
                                 ->orderBy('receipt_number', 'desc')
                                 ->first();
        
        if ($lastReceipt) {
            // Extract the sequence number
            $sequence = (int) substr($lastReceipt->receipt_number, -4);
            $sequence++;
        } else {
            $sequence = 1;
        }
        
        // Format the receipt number
        $receiptNumber = $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return $receiptNumber;
    }
}