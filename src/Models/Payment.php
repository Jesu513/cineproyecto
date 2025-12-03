<?php

namespace App\Models;

class Payment extends BaseModel
{
    protected string $table = 'payments';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'booking_id',
        'amount',
        'payment_method',
        'transaction_id',
        'card_last4',
        'card_brand',
        'status',
        'paid_at',
        'refunded_at',
        'refund_reason'
    ];

    protected array $casts = [
        'id' => 'int',
        'booking_id' => 'int',
        'amount' => 'float',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
