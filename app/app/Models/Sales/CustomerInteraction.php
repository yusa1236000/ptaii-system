<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInteraction extends Model
{
    use HasFactory;

    protected $table = 'CustomerInteraction';
    protected $primaryKey = 'interaction_id';
    public $timestamps = false;
    
    protected $fillable = [
        'customer_id',
        'interaction_date',
        'interaction_type',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'interaction_date' => 'date',
    ];

    /**
     * Get the customer that owns the customer interaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the user that owns the customer interaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}