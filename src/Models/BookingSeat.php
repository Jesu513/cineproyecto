<?php

namespace App\Models;

class BookingSeat extends BaseModel
{
    protected string $table = 'booking_seats';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'booking_id',
        'seat_id',
        'price'
    ];

    protected array $casts = [
        'id' => 'int',
        'booking_id' => 'int',
        'seat_id' => 'int',
        'price' => 'float',
        'created_at' => 'datetime'
    ];
}
