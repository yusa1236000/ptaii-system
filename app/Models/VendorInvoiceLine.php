<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorInvoiceLine extends Model
{
    use HasFactory;

    protected $table = 'vendor_invoice_lines';
    protected $primaryKey = 'line_id';
    protected $fillable = [
        'invoice_id',
        'po_line_id',
        'item_id',
        'quantity',
        'unit_price',
        'subtotal',
        'tax',
        'total',
        'base_currency_unit_price', // Baru
        'base_currency_subtotal', // Baru
        'base_currency_tax', // Baru
        'base_currency_total' // Baru
    ];
    
    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'subtotal' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'base_currency_unit_price' => 'float', // Baru
        'base_currency_subtotal' => 'float', // Baru
        'base_currency_tax' => 'float', // Baru
        'base_currency_total' => 'float' // Baru
    ];

    public function invoice()
    {
        return $this->belongsTo(VendorInvoice::class, 'invoice_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(POLine::class, 'po_line_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    
    /**
     * Get line amounts in specified currency.
     *
     * @param string $toCurrency
     * @param string|null $date
     * @return array
     */
    public function getAmountsInCurrency($toCurrency, $date = null)
    {
        $invoice = $this->invoice;
        $date = $date ?? $invoice->invoice_date;
        
        // If already in requested currency, return original amounts
        if ($invoice->currency_code === $toCurrency) {
            return [
                'unit_price' => $this->unit_price,
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'total' => $this->total
            ];
        }
        
        // Try to convert via base currency first
        if ($toCurrency === $invoice->base_currency) {
            return [
                'unit_price' => $this->base_currency_unit_price,
                'subtotal' => $this->base_currency_subtotal,
                'tax' => $this->base_currency_tax,
                'total' => $this->base_currency_total
            ];
        }
        
        // Get rate from base currency to requested currency
        $rate = CurrencyRate::getCurrentRate($invoice->base_currency, $toCurrency, $date);
        
        if (!$rate) {
            // Try direct conversion
            $rate = CurrencyRate::getCurrentRate($invoice->currency_code, $toCurrency, $date);
            if (!$rate) {
                // If no conversion possible, return original values
                return [
                    'unit_price' => $this->unit_price,
                    'subtotal' => $this->subtotal,
                    'tax' => $this->tax,
                    'total' => $this->total
                ];
            }
            
            return [
                'unit_price' => $this->unit_price * $rate,
                'subtotal' => $this->subtotal * $rate,
                'tax' => $this->tax * $rate,
                'total' => $this->total * $rate
            ];
        }
        
        return [
            'unit_price' => $this->base_currency_unit_price * $rate,
            'subtotal' => $this->base_currency_subtotal * $rate,
            'tax' => $this->base_currency_tax * $rate,
            'total' => $this->base_currency_total * $rate
        ];
    }
}