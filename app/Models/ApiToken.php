<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ApiToken extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'api_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
