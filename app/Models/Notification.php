<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Notification extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'is_read',
        'type',
        'sender_id',
        'sender_name',
        'sender_role',
    ];
}
