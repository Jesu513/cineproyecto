<?php

namespace App\Models;

class Booking extends BaseModel
{
    protected string $table = 'bookings';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'showtime_id',
        'booking_code',
        'total_seats',
        'total_amount',
        'discount_amount',
        'final_amount',
        'status',
        'reserved_until',
        'payment_method',
        'notes'
    ];

    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'showtime_id' => 'int',
        'total_seats' => 'int',
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'final_amount' => 'float',
        'reserved_until' => 'datetime',
        'created_at' => 'datetime'
    ];
}
