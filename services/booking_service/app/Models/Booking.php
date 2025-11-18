<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    
    protected $fillable = [
        'user_id',
        'booking_reference',
        'booking_date',
        'start_time',
        'end_time',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }
        public function resources(): HasMany
    {
        return $this->hasMany(BookingDetail::class)->where('item_type', 'resource');
    }

    // Only booking items
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingDetail::class)->where('item_type', 'booking_item');
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        } while (self::where('booking_reference', $reference)->exists());

        return $reference;
    }
    public function calculateHours(): floatval
    {
        $start = \Carbon\carbon::parse($this->start_time);
        $end = \Carbon\carbon::parse($this->end_time);
        return $end->diffInMinutes($start) / 60;

    }
}
