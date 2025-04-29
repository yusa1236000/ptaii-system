<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POLine extends Model
{
    use HasFactory;

    protected $table = 'po_lines';
    protected $primaryKey = 'line_id';
    protected $fillable = [
        'po_id',
        'item_id',
        'unit_price',
        'quantity',
        'uom_id',
        'subtotal',
        'tax',
        'total',
        'base_currency_unit_price', // Baru
        'base_currency_subtotal', // Baru
        'base_currency_tax', // Baru
        'base_currency_total', // Baru
    ];
    
    protected $casts = [
        'unit_price' => 'float',
        'quantity' => 'float',
        'subtotal' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'base_currency_unit_price' => 'float', // Baru
        'base_currency_subtotal' => 'float', // Baru
        'base_currency_tax' => 'float', // Baru
        'base_currency_total' => 'float', // Baru
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function goodsReceiptLines()
    {
        return $this->hasMany(GoodsReceiptLine::class, 'po_line_id');
    }

    public function invoiceLines()
    {
        return $this->hasMany(VendorInvoiceLine::class, 'po_line_id');
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
        $purchaseOrder = $this->purchaseOrder;
        
        // If already in requested currency, return original amounts
        if ($purchaseOrder->currency_code === $toCurrency) {
            return [
                'unit_price' => $this->unit_price,
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'total' => $this->total
            ];
        }
        
        // Try to convert via base currency first
        if ($toCurrency === $purchaseOrder->base_currency) {
            return [
                'unit_price' => $this->base_currency_unit_price,
                'subtotal' => $this->base_currency_subtotal,
                'tax' => $this->base_currency_tax,
                'total' => $this->base_currency_total
            ];
        }
        
        // Get rate from base currency to requested currency
        $rate = CurrencyRate::getCurrentRate($purchaseOrder->base_currency, $toCurrency, $date);
        
        if (!$rate) {
            // Try direct conversion
            $rate = CurrencyRate::getCurrentRate($purchaseOrder->currency_code, $toCurrency, $date);
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