<?php

namespace App\Services;

use App\Models\PurchaseRequisition;
use Carbon\Carbon;

class PRNumberGenerator
{
    public function generate()
    {
        $prefix = 'PR';
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        
        // Get the last PR number
        $lastPR = PurchaseRequisition::where('pr_number', 'like', "{$prefix}{$year}{$month}%")
                                 ->orderBy('pr_number', 'desc')
                                 ->first();
        
        if ($lastPR) {
            // Extract the sequence number
            $sequence = (int) substr($lastPR->pr_number, -4);
            $sequence++;
        } else {
            $sequence = 1;
        }
        
        // Format the PR number
        $prNumber = $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return $prNumber;
    }
}