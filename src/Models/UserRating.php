<?php

namespace App\Models;

class UserRating extends BaseModel
{
    protected string $table = 'user_ratings';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'movie_id',
        'rating',
        'review',
        'is_verified_purchase',
        'helpful_count'
    ];

    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'movie_id' => 'int',
        'rating' => 'int',
        'is_verified_purchase' => 'bool',
        'helpful_count' => 'int',
        'created_at' => 'datetime'
    ];
}
