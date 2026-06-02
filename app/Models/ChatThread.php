<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ChatThread extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'chat_threads';

    protected $fillable = [
        'thread_id',
        'admin_username',
        'campus_key',
        'participant_id',
        'participant_name',
        'participant_role',
        'peer_a_id',
        'peer_a_name',
        'peer_b_id',
        'peer_b_name',
        'messages',
        'peer_profile_photo',
    ];
}
