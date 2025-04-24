<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Models\Accounting\CustomerReceivable;
use App\Models\Accounting\ReceivablePayment;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReceivablePaymentController extends Controller
{
    /**
     * Display a listing of receivable payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ReceivablePayment::with('customerReceivable.customer');
        
        // Filter by receivable
        if ($request->has('receivable_id')) {
            $query->where('receivable_id', $request->receivable_id);
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
     * Store a newly created receivable payment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receivable_id' => 'required|exists:CustomerReceivable,receivable_id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'required|string|max:50',
            'create_journal_entry' => 'boolean',
            'cash_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id',
            'receivable_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Get the receivable
        $receivable = CustomerReceivable::findOrFail($request->receivable_id);
        
        // Check if receivable is already paid
        if ($receivable->status === 'Paid') {
            return response()->json([
                'message' => 'This receivable has already been fully paid'
            ], 422);
        }
        
        // Check if payment amount is valid
        if ($request->amount > $receivable->balance) {
            return response()->json([
                'message' => 'Payment amount cannot exceed the remaining balance of ' . $receivable->balance
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Create payment
            $payment = ReceivablePayment::create([
                'receivable_id' => $request->receivable_id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number
            ]);
            
            // Update receivable
            $receivable->paid_amount += $request->amount;
            $receivable->balance -= $request->amount;
            
            // Update status if fully paid
            if ($receivable->balance <= 0) {
                $receivable->status = 'Paid';
            }
            
            $receivable->save();
            
            // Create journal entry if requested
            if ($request->input('create_journal_entry', false)) {
                // Validate required account IDs
                if (!$request->has('cash_account_id') || !$request->has('receivable_account_id')) {
                    throw new \Exception('Cash and receivable account IDs are required');
                }
                
                // Create journal entry
                $journalEntry = JournalEntry::create([
                    'journal_number' => 'RECPMT-' . date('YmdHis'),
                    'entry_date' => $request->payment_date,
                    'reference_type' => 'ReceivablePayment',
                    'reference_id' => $payment->payment_id,
                    'description' => 'Payment from ' . $receivable->customer->name . ' for invoice ' . $receivable->salesInvoice->invoice_number,
                    'period_id' => $this->getCurrentPeriodId(),
                    'status' => 'Posted'
                ]);
                
                // Create journal entry lines
                // Debit Cash/Bank
                JournalEntryLine::create([
                    'journal_id' => $journalEntry->journal_id,
                    'account_id' => $request->cash_account_id,
                    'debit_amount' => $request->amount,
                    'credit_amount' => 0,
                    'description' => 'Payment from ' . $receivable->customer->name
                ]);
                
                // Credit Accounts Receivable
                JournalEntryLine::create([
                    'journal_id' => $journalEntry->journal_id,
                    'account_id' => $request->receivable_account_id,
                    'debit_amount' => 0,
                    'credit_amount' => $request->amount,
                    'description' => 'Payment from ' . $receivable->customer->name
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'data' => $payment, 
                'message' => 'Receivable payment created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create receivable payment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified receivable payment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payment = ReceivablePayment::with('customerReceivable.customer')
            ->findOrFail($id);
        
        return response()->json(['data' => $payment], 200);
    }

    /**
     * Remove the specified receivable payment from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payment = ReceivablePayment::findOrFail($id);
        $receivable = CustomerReceivable::findOrFail($payment->receivable_id);
        
        try {
            DB::beginTransaction();
            
            // Update receivable
            $receivable->paid_amount -= $payment->amount;
            $receivable->balance += $payment->amount;
            
            // Update status
            if ($receivable->balance > 0) {
                $receivable->status = 'Open';
            }
            
            $receivable->save();
            
            // Find and delete any related journal entry
            $journalEntry = JournalEntry::where('reference_type', 'ReceivablePayment')
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
            
            return response()->json(['message' => 'Receivable payment deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete receivable payment: ' . $e->getMessage()], 500);
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