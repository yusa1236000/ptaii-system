<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'Customer';
    protected $primaryKey = 'customer_id';
    public $timestamps = false;

    protected $fillable = [
        'customer_code',
        'name',
        'address',
        'tax_id',
        'contact_person',
        'phone',
        'email',
        'status'
    ];

    /**
     * Get the sales quotations for the customer.
     */
    public function salesQuotations(): HasMany
    {
        return $this->hasMany(SalesQuotation::class, 'customer_id');
    }

    /**
     * Get the sales orders for the customer.
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    /**
     * Get the deliveries for the customer.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'customer_id');
    }

    /**
     * Get the sales invoices for the customer.
     */
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'customer_id');
    }

    /**
     * Get the sales returns for the customer.
     */
    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'customer_id');
    }

    /**
     * Get the customer interactions for the customer.
     */
    public function customerInteractions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class, 'customer_id');
    }

    /**
     * Get the sales forecasts for the customer.
     */
    public function salesForecasts(): HasMany
    {
        return $this->hasMany(SalesForecast::class, 'customer_id');
    }
}