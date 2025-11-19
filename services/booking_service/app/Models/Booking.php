<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    
    protected $fillable = [
        'user_id',
        'user_email',
        'user_type',
        'is_verified',
        'otp_code',
        'otp_expires_at',
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
        'is_verified' => 'boolean',
        'otp_expires_at' => 'datetime',
    ];
    protected $hidden = [
        'otp_code',
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
    // Generate a unique booking reference
    public static function generateReference(): string
    {
        do {
            $reference = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        } while (self::where('booking_reference', $reference)->exists());

        return $reference;
    }
    //generate OTP code
    public static function generateOTP(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    //check if email is university domain
    public static function isUniversityEmail(string $email): bool
    {
        $universityDomain = '@sjp.ac.lk';
        return str_ends_with(strtolower($email), $universityDomain);
    }

    //determine user type based on email
    public static function getUserType(string $email): string
    {
        return self::isUniversityEmail($email) ? 'internal' : 'external';
    }

    //check if OTP is valid
    public function isOTPValid(string $otp): bool
    {
        if ($this->otp_code !== $otp) {
            return false;
        }
        if ($this->otp_expires_at && $this->otp_expires_at->isPast()) {
            return false;
        }
        return true;
    }

    // Calculate total hours between start_time and end_time
    public function calculateHours(): float
    {
        $start = \Carbon\carbon::parse($this->start_time);
        $end = \Carbon\carbon::parse($this->end_time);
        return $end->diffInMinutes($start) / 60;

    }
}
