<?php

namespace App\Models;

class Promotion extends BaseModel
{
    protected string $table = 'promotions';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_tickets',
        'max_discount',
        'valid_from',
        'valid_until',
        'max_uses',
        'max_uses_per_user',
        'uses_count',
        'applicable_days',
        'applicable_movies',
        'is_active'
    ];

    protected array $casts = [
        'id' => 'int',
        'discount_value' => 'float',
        'min_tickets' => 'int',
        'max_discount' => 'float',
        'max_uses' => 'int',
        'max_uses_per_user' => 'int',
        'uses_count' => 'int',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'applicable_days' => 'json',
        'applicable_movies' => 'json',
        'is_active' => 'bool'
    ];
}
