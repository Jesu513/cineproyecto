<?php

namespace App\Models;

class Notification extends BaseModel
{
    protected string $table = 'notifications';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at'
    ];

    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'data' => 'json',
        'is_read' => 'bool',
        'read_at' => 'datetime',
        'created_at' => 'datetime'
    ];
}
