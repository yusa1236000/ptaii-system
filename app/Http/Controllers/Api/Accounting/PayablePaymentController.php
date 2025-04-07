<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Models\Accounting\VendorPayable;
use App\Models\Accounting\PayablePayment;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayablePaymentController extends Controller
{
    /**
     * Display a listing of payable payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = PayablePayment::with('vendorPayable.vendor');
        
        // Filter by payable
        if ($request->has('payable_id')) {
            $query->where('payable_id', $request->payable_id);
        }
        
        // Filter by payment date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }
        
        $payments = $query->orderBy('payment_date', 'desc')
            ->paginate($request->input('per_page', 15));
        
        return response()->json($payments, 200);
    }

    /**
     * Store a newly created payable payment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payable_id' => 'required|exists:VendorPayable,payable_id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'required|string|max:50',
            'create_journal_entry' => 'boolean',
            'cash_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id',
            'payable_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Get the payable
        $payable = VendorPayable::findOrFail($request->payable_id);
        
        // Check if payable is already paid
        if ($payable->status === 'Paid') {
            return response()->json([
                'message' => 'This payable has already been fully paid'
            ], 422);
        }
        
        // Check if payment amount is valid
        if ($request->amount > $payable->balance) {
            return response()->json([
                'message' => 'Payment amount cannot exceed the remaining balance of ' . $payable->balance
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Create payment
            $payment = PayablePayment::create([
                'payable_id' => $request->payable_id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number
            ]);
            
            // Update payable
            $payable->paid_amount += $request->amount;
            $payable->balance -= $request->amount;
            
            // Update status if fully paid
            if ($payable->balance <= 0) {
                $payable->status = 'Paid';
            }
            
            $payable->save();
            
            // Create journal entry if requested
            if ($request->input('create_journal_entry', false)) {
                // Validate required account IDs
                if (!$request->has('cash_account_id') || !$request->has('payable_account_id')) {
                    throw new \Exception('Cash and payable account IDs are required');
                }
                
                // Create journal entry
                $journalEntry = JournalEntry::create([
                    'journal_number' => 'PAYPMT-' . date('YmdHis'),
                    'entry_date' => $request->payment_date,
                    'reference_type' => 'PayablePayment',
                    'reference_id' => $payment->payment_id,
                    'description' => 'Payment to ' . $payable->vendor->name . ' for invoice ' . $payable->vendorInvoice->invoice_number,
                    'period_id' => $this->getCurrentPeriodId(),
                    'status' => 'Posted'
                ]);
                
                // Create journal entry lines
                // Debit Accounts Payable
                JournalEntryLine::create([
                    'journal_id' => $journalEntry->journal_id,
                    'account_id' => $request->payable_account_id,
                    'debit_amount' => $request->amount,
                    'credit_amount' => 0,
                    'description' => 'Payment to ' . $payable->vendor->name
                ]);
                
                // Credit Cash/Bank
                JournalEntryLine::create([
                    'journal_id' => $journalEntry->journal_id,
                    'account_id' => $request->cash_account_id,
                    'debit_amount' => 0,
                    'credit_amount' => $request->amount,
                    'description' => 'Payment to ' . $payable->vendor->name
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'data' => $payment, 
                'message' => 'Payable payment created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create payable payment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified payable payment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payment = PayablePayment::with('vendorPayable.vendor')
            ->findOrFail($id);
        
        return response()->json(['data' => $payment], 200);
    }

    /**
     * Remove the specified payable payment from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payment = PayablePayment::findOrFail($id);
        $payable = VendorPayable::findOrFail($payment->payable_id);
        
        try {
            DB::beginTransaction();
            
            // Update payable
            $payable->paid_amount -= $payment->amount;
            $payable->balance += $payment->amount;
            
            // Update status
            if ($payable->balance > 0) {
                $payable->status = 'Open';
            }
            
            $payable->save();
            
            // Find and delete any related journal entry
            $journalEntry = JournalEntry::where('reference_type', 'PayablePayment')
                ->where('reference_id', $payment->payment_id)
                ->first();
            
            if ($journalEntry) {
                // Delete journal entry lines
                JournalEntryLine::where('journal_id', $journalEntry->journal_id)->delete();
                
                // Delete journal entry
                $journalEntry->delete();
            }
            
            // Delete payment
            $payment->delete();
            
            DB::commit();
            
            return response()->json(['message' => 'Payable payment deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete payable payment: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Helper method to get the current accounting period ID.
     *
     * @return int
     */
    private function getCurrentPeriodId()
    {
        $currentPeriod = DB::table('AccountingPeriod')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('status', 'Open')
            ->first();
        
        if (!$currentPeriod) {
            throw new \Exception('No active accounting period found for the current date');
        }
        
        return $currentPeriod->period_id;
    }
}