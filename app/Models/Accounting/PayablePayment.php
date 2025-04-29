<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CurrencyRate;

class PayablePayment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'PayablePayment';

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
        'payable_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'payment_currency', // Baru
        'exchange_rate', // Baru
        'payable_amount', // Baru
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
        'payable_amount' => 'float', // Baru
        'exchange_difference' => 'float' // Baru
    ];

    /**
     * Get the vendor payable that owns the payment.
     */
    public function vendorPayable(): BelongsTo
    {
        return $this->belongsTo(VendorPayable::class, 'payable_id');
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
        
        // Get the payable to check its base currency
        $payable = $this->vendorPayable;
        
        // If converting to payable currency
        if ($toCurrency === $payable->currency_code) {
            return $this->payable_amount;
        }
        
        // If converting to base currency
        if ($toCurrency === $payable->base_currency) {
            // Calculate base currency amount from either payment or payable amount
            if ($this->payment_currency === $payable->base_currency) {
                return $this->amount;
            } else if ($payable->currency_code === $payable->base_currency) {
                return $this->payable_amount;
            } else {
                // Convert from payment currency to base currency
                $rate = CurrencyRate::getCurrentRate($this->payment_currency, $payable->base_currency, $date);
                if ($rate) {
                    return $this->amount * $rate;
                }
                
                // Try converting from payable amount if direct conversion fails
                $rate = CurrencyRate::getCurrentRate($payable->currency_code, $payable->base_currency, $date);
                if ($rate) {
                    return $this->payable_amount * $rate;
                }
            }
        }
        
        // For any other currency, try direct conversion from payment currency
        $rate = CurrencyRate::getCurrentRate($this->payment_currency, $toCurrency, $date);
        if ($rate) {
            return $this->amount * $rate;
        }
        
        // Try conversion via base currency
        $baseRate = CurrencyRate::getCurrentRate($this->payment_currency, $payable->base_currency, $date);
        $toRate = CurrencyRate::getCurrentRate($payable->base_currency, $toCurrency, $date);
        
        if ($baseRate && $toRate) {
            return $this->amount * $baseRate * $toRate;
        }
        
        // If all else fails, try to convert from payable currency
        $rate = CurrencyRate::getCurrentRate($payable->currency_code, $toCurrency, $date);
        if ($rate) {
            return $this->payable_amount * $rate;
        }
        
        // If no conversion possible, return original amount
        return $this->amount;
    }
}