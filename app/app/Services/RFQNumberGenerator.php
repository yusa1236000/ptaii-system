<?php

namespace App\Services;

use App\Models\RequestForQuotation;
use Carbon\Carbon;

class RFQNumberGenerator
{
    public function generate()
    {
        $prefix = 'RFQ';
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        
        // Get the last RFQ number
        $lastRFQ = RequestForQuotation::where('rfq_number', 'like', "{$prefix}{$year}{$month}%")
                                 ->orderBy('rfq_number', 'desc')
                                 ->first();
        
        if ($lastRFQ) {
            // Extract the sequence number
            $sequence = (int) substr($lastRFQ->rfq_number, -4);
            $sequence++;
        } else {
            $sequence = 1;
        }
        
        // Format the RFQ number
        $rfqNumber = $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return $rfqNumber;
    }
}