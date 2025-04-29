<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CurrencyRate;

class ReceivablePayment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ReceivablePayment';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'payment_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'receivable_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'payment_currency', // Baru
        'exchange_rate', // Baru
        'receivable_amount', // Baru
        'exchange_difference' // Baru
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
        'exchange_rate' => 'float', // Baru
        'receivable_amount' => 'float', // Baru
        'exchange_difference' => 'float' // Baru
    ];

    /**
     * Get the customer receivable that owns the payment.
     */
    public function customerReceivable(): BelongsTo
    {
        return $this->belongsTo(CustomerReceivable::class, 'receivable_id');
    }
    
    /**
     * Convert amount to specified currency.
     *
     * @param string $toCurrency
     * @param string|null $date
     * @return float
     */
    public function getAmountInCurrency($toCurrency, $date = null)
    {
        $date = $date ?? $this->payment_date;
        
        // If already in requested currency, return original amount
        if ($this->payment_currency === $toCurrency) {
            return $this->amount;
        }
        
        // Get the receivable to check its base currency
        $receivable = $this->customerReceivable;
        
        // If converting to receivable currency
        if ($toCurrency === $receivable->currency_code) {
            return $this->receivable_amount;
        }
        
        // If converting to base currency
        if ($toCurrency === $receivable->base_currency) {
            // Calculate base currency amount from either payment or receivable amount
            if ($this->payment_currency === $receivable->base_currency) {
                return $this->amount;
            } else if ($receivable->currency_code === $receivable->base_currency) {
                return $this->receivable_amount;
            } else {
                // Convert from payment currency to base currency
                $rate = CurrencyRate::getCurrentRate($this->payment_currency, $receivable->base_currency, $date);
                if ($rate) {
                    return $this->amount * $rate;
                }
                
                // Try converting from receivable amount if direct conversion fails
                $rate = CurrencyRate::getCurrentRate($receivable->currency_code, $receivable->base_currency, $date);
                if ($rate) {
                    return $this->receivable_amount * $rate;
                }
            }
        }
        
        // For any other currency, try direct conversion from payment currency
        $rate = CurrencyRate::getCurrentRate($this->payment_currency, $toCurrency, $date);
        if ($rate) {
            return $this->amount * $rate;
        }
        
        // Try conversion via base currency
        $baseRate = CurrencyRate::getCurrentRate($this->payment_currency, $receivable->base_currency, $date);
        $toRate = CurrencyRate::getCurrentRate($receivable->base_currency, $toCurrency, $date);
        
        if ($baseRate && $toRate) {
            return $this->amount * $baseRate * $toRate;
        }
        
        // If all else fails, try to convert from receivable currency
        $rate = CurrencyRate::getCurrentRate($receivable->currency_code, $toCurrency, $date);
        if ($rate) {
            return $this->receivable_amount * $rate;
        }
        
        // If no conversion possible, return original amount
        return $this->amount;
    }
}