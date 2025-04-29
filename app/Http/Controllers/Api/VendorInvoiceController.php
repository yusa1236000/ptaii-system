<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceLine;
use App\Models\PurchaseOrder;
use App\Models\POLine;
use App\Models\Accounting\VendorPayable;
use App\Models\Accounting\ExchangeRate;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Http\Requests\VendorInvoiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VendorInvoiceController extends Controller
{
    /**
     * Menampilkan daftar faktur vendor dengan filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = VendorInvoice::with(['vendor', 'purchaseOrder']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->has('po_id')) {
            $query->where('po_id', $request->po_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('invoice_number', 'like', "%{$search}%");
        }
        
        // Filter by currency
        if ($request->has('currency')) {
            $query->where('currency', $request->currency);
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'invoice_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $vendorInvoices = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendorInvoices
        ]);
    }

    /**
     * Menyimpan faktur vendor baru.
     *
     * @param  \App\Http\Requests\VendorInvoiceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(VendorInvoiceRequest $request)
    {
        // Check if Purchase Order exists and is in appropriate status
        $po = PurchaseOrder::findOrFail($request->po_id);
        if (!in_array($po->status, ['partial', 'received', 'completed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Faktur hanya dapat dibuat untuk PO yang telah menerima barang'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Set invoice currency (from request or from PO)
            $currency = $request->currency ?? $po->currency ?? config('app.base_currency', 'USD');
            $invoiceDate = $request->invoice_date;
            $baseCurrency = config('app.base_currency', 'USD');
            
            // Get exchange rate if currency is not base currency
            $exchangeRate = 1; // Default for base currency
            if ($currency !== $baseCurrency) {
                if ($request->has('exchange_rate')) {
                    $exchangeRate = $request->exchange_rate;
                } else {
                    // Fetch from database
                    $rateRecord = ExchangeRate::where('from_currency', $currency)
                        ->where('to_currency', $baseCurrency)
                        ->where('rate_date', '<=', $invoiceDate)
                        ->orderBy('rate_date', 'desc')
                        ->first();
                    
                    if ($rateRecord) {
                        $exchangeRate = $rateRecord->rate;
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Kurs mata uang tidak ditemukan untuk ' . $currency . '. Mohon masukkan kurs mata uang secara manual.'
                        ], 422);
                    }
                }
            }
            
            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;
            $invoiceLines = [];
            
            foreach ($request->lines as $line) {
                $poLine = null;
                if (isset($line['po_line_id'])) {
                    $poLine = POLine::find($line['po_line_id']);
                }
                
                $lineQuantity = $line['quantity'];
                $lineUnitPrice = $line['unit_price'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                
                // Use PO line currency or invoice currency
                $lineCurrency = $poLine && $poLine->currency ? $poLine->currency : $currency;
                $lineExchangeRate = $exchangeRate;
                
                // If line currency differs from invoice currency, we need to convert
                if ($lineCurrency !== $currency) {
                    // Get exchange rate from line currency to base currency
                    $lineToBaseRate = 1;
                    if ($lineCurrency !== $baseCurrency) {
                        $lineRateRecord = ExchangeRate::where('from_currency', $lineCurrency)
                            ->where('to_currency', $baseCurrency)
                            ->where('rate_date', '<=', $invoiceDate)
                            ->orderBy('rate_date', 'desc')
                            ->first();
                            
                        if ($lineRateRecord) {
                            $lineToBaseRate = $lineRateRecord->rate;
                        } else {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Kurs mata uang tidak ditemukan untuk ' . $lineCurrency . '. Mohon masukkan kurs mata uang secara manual.'
                            ], 422);
                        }
                    }
                    
                    // Convert line unit price to invoice currency via base currency
                    $baseUnitPrice = $lineUnitPrice * $lineToBaseRate;
                    $lineUnitPrice = $baseUnitPrice / $exchangeRate;
                }
                
                $lineSubtotal = $lineUnitPrice * $lineQuantity;
                $lineTotal = $lineSubtotal + $lineTax;
                
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
                
                $invoiceLines[] = [
                    'po_line_id' => $line['po_line_id'] ?? null,
                    'item_id' => $line['item_id'],
                    'quantity' => $lineQuantity,
                    'unit_price' => $lineUnitPrice,
                    'subtotal' => $lineSubtotal,
                    'tax' => $lineTax,
                    'total' => $lineTotal,
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'base_unit_price' => $lineUnitPrice * $exchangeRate
                ];
            }
            
            $totalAmount = $subtotal + $taxTotal;
            
            // Base currency amounts
            $baseCurrencyTotalAmount = $totalAmount * $exchangeRate;
            
            // Create vendor invoice
            $vendorInvoice = VendorInvoice::create([
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $invoiceDate,
                'vendor_id' => $po->vendor_id,
                'po_id' => $request->po_id,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxTotal,
                'due_date' => $request->due_date,
                'status' => 'pending',
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'base_currency_amount' => $baseCurrencyTotalAmount
            ]);
            
            // Create invoice lines
            foreach ($invoiceLines as $line) {
                $vendorInvoice->lines()->create($line);
            }
            
            // Create vendor payable record
            $vendorPayable = VendorPayable::create([
                'vendor_id' => $po->vendor_id,
                'invoice_id' => $vendorInvoice->invoice_id,
                'amount' => $baseCurrencyTotalAmount, // In base currency
                'due_date' => $request->due_date,
                'paid_amount' => 0,
                'balance' => $baseCurrencyTotalAmount, // In base currency
                'status' => 'Open',
                'currency' => $currency,
                'original_amount' => $totalAmount, // Original invoice amount
                'exchange_rate' => $exchangeRate
            ]);
            
            // Create accounting journal entry if needed
            if ($request->input('create_journal_entry', false)) {
                $this->createJournalEntry($vendorInvoice, $vendorPayable, $request);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Faktur Vendor berhasil dibuat',
                'data' => $vendorInvoice->load(['vendor', 'purchaseOrder', 'lines.item'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat Faktur Vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail faktur vendor tertentu.
     *
     * @param  \App\Models\VendorInvoice  $vendorInvoice
     * @return \Illuminate\Http\Response
     */
    public function show(VendorInvoice $vendorInvoice)
    {
        $vendorInvoice->load(['vendor', 'purchaseOrder', 'lines.item']);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendorInvoice
        ]);
    }

    /**
     * Memperbarui faktur vendor yang ada.
     *
     * @param  \App\Http\Requests\VendorInvoiceRequest  $request
     * @param  \App\Models\VendorInvoice  $vendorInvoice
     * @return \Illuminate\Http\Response
     */
    public function update(VendorInvoiceRequest $request, VendorInvoice $vendorInvoice)
    {
        // Check if invoice can be updated (only pending status)
        if ($vendorInvoice->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya faktur dengan status pending yang dapat diperbarui'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Get existing currency information
            $currency = $request->currency ?? $vendorInvoice->currency;
            $invoiceDate = $request->invoice_date ?? $vendorInvoice->invoice_date;
            $baseCurrency = config('app.base_currency', 'USD');
            
            // Get exchange rate if currency is not base currency
            $exchangeRate = 1; // Default for base currency
            if ($currency !== $baseCurrency) {
                if ($request->has('exchange_rate')) {
                    $exchangeRate = $request->exchange_rate;
                } else if ($vendorInvoice->exchange_rate) {
                    $exchangeRate = $vendorInvoice->exchange_rate;
                } else {
                    // Fetch from database
                    $rateRecord = ExchangeRate::where('from_currency', $currency)
                        ->where('to_currency', $baseCurrency)
                        ->where('rate_date', '<=', $invoiceDate)
                        ->orderBy('rate_date', 'desc')
                        ->first();
                    
                    if ($rateRecord) {
                        $exchangeRate = $rateRecord->rate;
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Kurs mata uang tidak ditemukan untuk ' . $currency . '. Mohon masukkan kurs mata uang secara manual.'
                        ], 422);
                    }
                }
            }
            
            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;
            $invoiceLines = [];
            
            foreach ($request->lines as $line) {
                $poLine = null;
                if (isset($line['po_line_id'])) {
                    $poLine = POLine::find($line['po_line_id']);
                }
                
                $lineQuantity = $line['quantity'];
                $lineUnitPrice = $line['unit_price'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                
                // Use PO line currency or invoice currency
                $lineCurrency = $poLine && $poLine->currency ? $poLine->currency : $currency;
                $lineExchangeRate = $exchangeRate;
                
                // If line currency differs from invoice currency, we need to convert
                if ($lineCurrency !== $currency) {
                    // Get exchange rate from line currency to base currency
                    $lineToBaseRate = 1;
                    if ($lineCurrency !== $baseCurrency) {
                        $lineRateRecord = ExchangeRate::where('from_currency', $lineCurrency)
                            ->where('to_currency', $baseCurrency)
                            ->where('rate_date', '<=', $invoiceDate)
                            ->orderBy('rate_date', 'desc')
                            ->first();
                            
                        if ($lineRateRecord) {
                            $lineToBaseRate = $lineRateRecord->rate;
                        } else {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Kurs mata uang tidak ditemukan untuk ' . $lineCurrency . '. Mohon masukkan kurs mata uang secara manual.'
                            ], 422);
                        }
                    }
                    
                    // Convert line unit price to invoice currency via base currency
                    $baseUnitPrice = $lineUnitPrice * $lineToBaseRate;
                    $lineUnitPrice = $baseUnitPrice / $exchangeRate;
                }
                
                $lineSubtotal = $lineUnitPrice * $lineQuantity;
                $lineTotal = $lineSubtotal + $lineTax;
                
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
                
                $invoiceLines[] = [
                    'po_line_id' => $line['po_line_id'] ?? null,
                    'item_id' => $line['item_id'],
                    'quantity' => $lineQuantity,
                    'unit_price' => $lineUnitPrice,
                    'subtotal' => $lineSubtotal,
                    'tax' => $lineTax,
                    'total' => $lineTotal,
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'base_unit_price' => $lineUnitPrice * $exchangeRate
                ];
            }
            
            $totalAmount = $subtotal + $taxTotal;
            
            // Base currency amounts
            $baseCurrencyTotalAmount = $totalAmount * $exchangeRate;
            
            // Update vendor invoice
            $vendorInvoice->update([
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $invoiceDate,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxTotal,
                'due_date' => $request->due_date,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'base_currency_amount' => $baseCurrencyTotalAmount
            ]);
            
            // Update invoice lines
            // Delete existing lines
            $vendorInvoice->lines()->delete();
            
            // Create new lines
            foreach ($invoiceLines as $line) {
                $vendorInvoice->lines()->create($line);
            }
            
            // Update vendor payable
            $vendorPayable = VendorPayable::where('invoice_id', $vendorInvoice->invoice_id)->first();
            if ($vendorPayable) {
                // Calculate remaining balance
                $newBalance = $baseCurrencyTotalAmount - $vendorPayable->paid_amount;
                
                $vendorPayable->update([
                    'amount' => $baseCurrencyTotalAmount,
                    'due_date' => $request->due_date,
                    'balance' => $newBalance,
                    'currency' => $currency,
                    'original_amount' => $totalAmount,
                    'exchange_rate' => $exchangeRate
                ]);
            }
            
            // Update accounting journal entry if needed
            if ($request->input('update_journal_entry', false)) {
                $this->updateJournalEntry($vendorInvoice, $vendorPayable, $request);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Faktur Vendor berhasil diperbarui',
                'data' => $vendorInvoice->load(['vendor', 'purchaseOrder', 'lines.item'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui Faktur Vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus faktur vendor dari penyimpanan.
     *
     * @param  \App\Models\VendorInvoice  $vendorInvoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(VendorInvoice $vendorInvoice)
    {
        // Check if the invoice has related payments
        $vendorPayable = VendorPayable::where('invoice_id', $vendorInvoice->invoice_id)->first();
        if ($vendorPayable && $vendorPayable->paid_amount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus faktur yang telah memiliki pembayaran'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete related journal entries
            $journalEntries = JournalEntry::where('reference_type', 'VendorInvoice')
                ->where('reference_id', $vendorInvoice->invoice_id)
                ->get();
                
            foreach ($journalEntries as $journalEntry) {
                JournalEntryLine::where('journal_id', $journalEntry->journal_id)->delete();
                $journalEntry->delete();
            }
            
            // Delete related vendor payable
            if ($vendorPayable) {
                $vendorPayable->delete();
            }
            
            // Delete invoice lines
            $vendorInvoice->lines()->delete();
            
            // Delete the invoice
            $vendorInvoice->delete();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Faktur Vendor berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus Faktur Vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Memperbarui status faktur vendor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VendorInvoice  $vendorInvoice
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, VendorInvoice $vendorInvoice)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,paid,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $oldStatus = $vendorInvoice->status;
        $newStatus = $request->status;
        
        // Define allowed status transitions
        $allowedTransitions = [
            'pending' => ['approved', 'cancelled'],
            'approved' => ['paid', 'cancelled'],
            'paid' => ['cancelled'],
            'cancelled' => []
        ];
        
        if (!in_array($newStatus, $allowedTransitions[$oldStatus])) {
            return response()->json([
                'status' => 'error',
                'message' => "Status tidak dapat diubah dari {$oldStatus} ke {$newStatus}"
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update invoice status
            $vendorInvoice->update([
                'status' => $newStatus
            ]);
            
            // Update related vendor payable status if exists
            $vendorPayable = VendorPayable::where('invoice_id', $vendorInvoice->invoice_id)->first();
            if ($vendorPayable) {
                if ($newStatus === 'paid') {
                    $vendorPayable->update(['status' => 'Paid']);
                } else if ($newStatus === 'cancelled') {
                    $vendorPayable->update(['status' => 'Cancelled']);
                } else if ($newStatus === 'approved') {
                    $vendorPayable->update(['status' => 'Open']);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Status Faktur Vendor berhasil diperbarui',
                'data' => $vendorInvoice
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui status Faktur Vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mengambil kurs mata uang untuk tanggal tertentu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getExchangeRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|size:3',
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $currency = $request->currency;
        $date = $request->date;
        $baseCurrency = config('app.base_currency', 'USD');
        
        // If requesting base currency, rate is always 1
        if ($currency === $baseCurrency) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'currency' => $baseCurrency,
                    'rate' => 1,
                    'date' => $date
                ]
            ]);
        }
        
        // Get exchange rate
        $rate = ExchangeRate::where('from_currency', $currency)
            ->where('to_currency', $baseCurrency)
            ->where('rate_date', '<=', $date)
            ->orderBy('rate_date', 'desc')
            ->first();
        
        if (!$rate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kurs mata uang tidak ditemukan untuk ' . $currency . ' pada tanggal ' . $date
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'currency' => $currency,
                'rate' => $rate->rate,
                'date' => $rate->rate_date
            ]
        ]);
    }
    
    /**
     * Membuat entri jurnal untuk faktur vendor.
     *
     * @param  \App\Models\VendorInvoice  $vendorInvoice
     * @param  \App\Models\Accounting\VendorPayable  $vendorPayable
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function createJournalEntry(VendorInvoice $vendorInvoice, VendorPayable $vendorPayable, $request)
    {
        // Validate required account IDs
        $validator = Validator::make($request->all(), [
            'ap_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id',
            'expense_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id',
            'tax_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id'
        ]);
        
        if ($validator->fails()) {
            throw new \Exception('Akun GL diperlukan untuk membuat entri jurnal: ' . json_encode($validator->errors()));
        }
        
        // Get account IDs
        $apAccountId = $request->ap_account_id;
        $expenseAccountId = $request->expense_account_id;
        $taxAccountId = $request->tax_account_id;
        
        // Get current period
        $periodId = $this->getCurrentPeriodId();
        
        // Create journal entry
        $journalEntry = JournalEntry::create([
            'journal_number' => 'VINV-' . $vendorInvoice->invoice_id,
            'entry_date' => $vendorInvoice->invoice_date,
            'reference_type' => 'VendorInvoice',
            'reference_id' => $vendorInvoice->invoice_id,
            'description' => 'Faktur dari ' . $vendorInvoice->vendor->name . ' - ' . $vendorInvoice->invoice_number,
            'period_id' => $periodId,
            'status' => 'Posted'
        ]);
        
        // Calculate amounts
        $subtotal = $vendorInvoice->total_amount - $vendorInvoice->tax_amount;
        $taxAmount = $vendorInvoice->tax_amount;
        $totalAmount = $vendorInvoice->total_amount;
        
        // Base currency amounts
        $baseCurrencySubtotal = $subtotal * $vendorInvoice->exchange_rate;
        $baseCurrencyTaxAmount = $taxAmount * $vendorInvoice->exchange_rate;
        $baseCurrencyTotalAmount = $totalAmount * $vendorInvoice->exchange_rate;
        
        // Create journal entry lines
        
        // Debit Expense Account (subtotal amount)
        JournalEntryLine::create([
            'journal_id' => $journalEntry->journal_id,
            'account_id' => $expenseAccountId,
            'debit_amount' => $baseCurrencySubtotal,
            'credit_amount' => 0,
            'description' => 'Biaya dari ' . $vendorInvoice->vendor->name,
            'currency' => $vendorInvoice->currency,
            'foreign_amount' => $subtotal
        ]);
        
        // Debit Tax Account (tax amount, if any)
        if ($taxAmount > 0) {
            JournalEntryLine::create([
                'journal_id' => $journalEntry->journal_id,
                'account_id' => $taxAccountId,
                'debit_amount' => $baseCurrencyTaxAmount,
                'credit_amount' => 0,
                'description' => 'Pajak dari ' . $vendorInvoice->vendor->name,
                'currency' => $vendorInvoice->currency,
                'foreign_amount' => $taxAmount
            ]);
        }
        
        // Credit Accounts Payable (total amount)
        JournalEntryLine::create([
            'journal_id' => $journalEntry->journal_id,
            'account_id' => $apAccountId,
            'debit_amount' => 0,
            'credit_amount' => $baseCurrencyTotalAmount,
            'description' => 'Hutang ke ' . $vendorInvoice->vendor->name,
            'currency' => $vendorInvoice->currency,
            'foreign_amount' => $totalAmount
        ]);
    }
    
    /**
     * Memperbarui entri jurnal untuk faktur vendor.
     *
     * @param  \App\Models\VendorInvoice  $vendorInvoice
     * @param  \App\Models\Accounting\VendorPayable  $vendorPayable
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function updateJournalEntry(VendorInvoice $vendorInvoice, VendorPayable $vendorPayable, $request)
    {
        // Find existing journal entry
        $journalEntry = JournalEntry::where('reference_type', 'VendorInvoice')
            ->where('reference_id', $vendorInvoice->invoice_id)
            ->first();
            
        if (!$journalEntry) {
            // If no existing journal entry, create a new one
            $this->createJournalEntry($vendorInvoice, $vendorPayable, $request);
            return;
        }
        
        // Validate required account IDs
        $validator = Validator::make($request->all(), [
            'ap_account_id' => 'required_if:update_journal_entry,true|exists:ChartOfAccount,account_id',
            'expense_account_id' => 'required_if:update_journal_entry,true|exists:ChartOfAccount,account_id',
            'tax_account_id' => 'required_if:update_journal_entry,true|exists:ChartOfAccount,account_id'
        ]);
        
        if ($validator->fails()) {
            throw new \Exception('Akun GL diperlukan untuk memperbarui entri jurnal: ' . json_encode($validator->errors()));
        }
        
        // Get account IDs
        $apAccountId = $request->ap_account_id;
        $expenseAccountId = $request->expense_account_id;
        $taxAccountId = $request->tax_account_id;
        
        // Update journal entry header
        $journalEntry->update([
            'entry_date' => $vendorInvoice->invoice_date,
            'description' => 'Faktur dari ' . $vendorInvoice->vendor->name . ' - ' . $vendorInvoice->invoice_number
        ]);
        
        // Delete existing journal entry lines
        JournalEntryLine::where('journal_id', $journalEntry->journal_id)->delete();
        
        // Calculate amounts
        $subtotal = $vendorInvoice->total_amount - $vendorInvoice->tax_amount;
        $taxAmount = $vendorInvoice->tax_amount;
        $totalAmount = $vendorInvoice->total_amount;
        
        // Base currency amounts
        $baseCurrencySubtotal = $subtotal * $vendorInvoice->exchange_rate;
        $baseCurrencyTaxAmount = $taxAmount * $vendorInvoice->exchange_rate;
        $baseCurrencyTotalAmount = $totalAmount * $vendorInvoice->exchange_rate;
        
        // Create new journal entry lines
        
        // Debit Expense Account (subtotal amount)
        JournalEntryLine::create([
            'journal_id' => $journalEntry->journal_id,
            'account_id' => $expenseAccountId,
            'debit_amount' => $baseCurrencySubtotal,
            'credit_amount' => 0,
            'description' => 'Biaya dari ' . $vendorInvoice->vendor->name,
            'currency' => $vendorInvoice->currency,
            'foreign_amount' => $subtotal
        ]);
        
        // Debit Tax Account (tax amount, if any)
        if ($taxAmount > 0) {
            JournalEntryLine::create([
                'journal_id' => $journalEntry->journal_id,
                'account_id' => $taxAccountId,
                'debit_amount' => $baseCurrencyTaxAmount,
                'credit_amount' => 0,
                'description' => 'Pajak dari ' . $vendorInvoice->vendor->name,
                'currency' => $vendorInvoice->currency,
                'foreign_amount' => $taxAmount
            ]);
        }
        
        // Credit Accounts Payable (total amount)
        JournalEntryLine::create([
            'journal_id' => $journalEntry->journal_id,
            'account_id' => $apAccountId,
            'debit_amount' => 0,
            'credit_amount' => $baseCurrencyTotalAmount,
            'description' => 'Hutang ke ' . $vendorInvoice->vendor->name,
            'currency' => $vendorInvoice->currency,
            'foreign_amount' => $totalAmount
        ]);
    }
    
    /**
     * Get current accounting period ID.
     *
     * @return int
     * @throws \Exception
     */
    private function getCurrentPeriodId()
    {
        $currentPeriod = DB::table('AccountingPeriod')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('status', 'Open')
            ->first();
        
        if (!$currentPeriod) {
            throw new \Exception('Tidak ada periode akuntansi yang aktif untuk tanggal saat ini');
        }
        
        return $currentPeriod->period_id;
    }
}