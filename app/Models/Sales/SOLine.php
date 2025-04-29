<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Models\CurrencyRate;

class SOLine extends Model
{
    use HasFactory;

    protected $primaryKey = 'line_id';
    protected $table = 'SOLine';
    public $timestamps = false;

    protected $fillable = [
        'so_id',
        'item_id',
        'unit_price',
        'quantity',
        'uom_id',
        'discount',
        'subtotal',
        'tax',
        'total',
        'base_currency_unit_price', // Baru
        'base_currency_subtotal', // Baru
        'base_currency_discount', // Baru
        'base_currency_tax', // Baru
        'base_currency_total' // Baru
    ];
    
    protected $casts = [
        'unit_price' => 'float',
        'quantity' => 'float',
        'discount' => 'float',
        'subtotal' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'base_currency_unit_price' => 'float', // Baru
        'base_currency_subtotal' => 'float', // Baru
        'base_currency_discount' => 'float', // Baru
        'base_currency_tax' => 'float', // Baru
        'base_currency_total' => 'float' // Baru
    ];

    /**
     * Get the sales order that owns the sales order line.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    /**
     * Get the item that the sales order line belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get the unit of measure that the sales order line belongs to.
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    /**
     * Get the delivery lines for the sales order line.
     */
    public function deliveryLines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class, 'so_line_id');
    }

    /**
     * Get the sales invoice lines for the sales order line.
     */
    public function salesInvoiceLines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'so_line_id');
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
        $salesOrder = $this->salesOrder;
        $date = $date ?? $salesOrder->so_date;
        
        // If already in requested currency, return original amounts
        if ($salesOrder->currency_code === $toCurrency) {
            return [
                'unit_price' => $this->unit_price,
                'subtotal' => $this->subtotal,
                'discount' => $this->discount,
                'tax' => $this->tax,
                'total' => $this->total
            ];
        }
        
        // Try to convert via base currency first
        if ($toCurrency === $salesOrder->base_currency) {
            return [
                'unit_price' => $this->base_currency_unit_price,
                'subtotal' => $this->base_currency_subtotal,
                'discount' => $this->base_currency_discount,
                'tax' => $this->base_currency_tax,
                'total' => $this->base_currency_total
            ];
        }
        
        // Get rate from base currency to requested currency
        $rate = CurrencyRate::getCurrentRate($salesOrder->base_currency, $toCurrency, $date);
        
        if (!$rate) {
            // Try direct conversion
            $rate = CurrencyRate::getCurrentRate($salesOrder->currency_code, $toCurrency, $date);
            if (!$rate) {
                // If no conversion possible, return original values
                return [
                    'unit_price' => $this->unit_price,
                    'subtotal' => $this->subtotal,
                    'discount' => $this->discount,
                    'tax' => $this->tax,
                    'total' => $this->total
                ];
            }
            
            return [
                'unit_price' => $this->unit_price * $rate,
                'subtotal' => $this->subtotal * $rate,
                'discount' => $this->discount * $rate,
                'tax' => $this->tax * $rate,
                'total' => $this->total * $rate
            ];
        }
        
        return [
            'unit_price' => $this->base_currency_unit_price * $rate,
            'subtotal' => $this->base_currency_subtotal * $rate,
            'discount' => $this->base_currency_discount * $rate,
            'tax' => $this->base_currency_tax * $rate,
            'total' => $this->base_currency_total * $rate
        ];
    }
}