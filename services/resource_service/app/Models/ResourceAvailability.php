<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Resource;

class ResourceAvailability extends Model
{
    protected $table = 'resource_availability';

    protected $fillable = [
        'resource_id',
        'day_of_week',
        'day_name',
        'is_available',
        'start_time',
        'end_time',
    ];
    protected $casts = [
        'is_available' => 'boolean',
        'day_of_week' => 'integer',
    ];

    public static $dayMap = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7,
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public static function getDayNumber(string $dayName): ?int
    {
        return self::$dayMap[$dayName] ?? 1;
    }

    public static function getDayName(int $dayNumber): ?string
    {
        return array_search($dayNumber, self::$dayMap) ?: 'Monday';
    }

};
