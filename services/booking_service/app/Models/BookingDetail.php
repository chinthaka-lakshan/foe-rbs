<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDetail extends Model
{
    protected $fillable = [
        'booking_id',
        'item_type',
        'item_id',
        'item_name',
        'item_code',
        'quantity',
        'price_per_hour',
        'hours',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_hour' => 'decimal:2',
        'hours' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
