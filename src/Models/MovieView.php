<?php

namespace App\Models;

class MovieView extends BaseModel
{
    protected string $table = 'movie_views';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'movie_id',
        'view_count',
        'last_viewed_at'
    ];

    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'movie_id' => 'int',
        'view_count' => 'int',
        'last_viewed_at' => 'datetime'
    ];
}
