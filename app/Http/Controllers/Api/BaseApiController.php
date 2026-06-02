<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use MongoDB\Client;

abstract class BaseApiController extends Controller
{
    protected function publicUser(object|array $user): array
    {
        $array = json_decode(json_encode($user), true);
        $array['id'] = $array['_id']['$oid'] ?? (string) ($array['_id'] ?? '');
        unset($array['_id'], $array['password']);

        return $array;
    }

    protected function users()
    {
        return $this->db()->selectCollection('users');
    }

    protected function db()
    {
        return (new Client(config('database.connections.mongodb.dsn')))
            ->selectDatabase(config('database.connections.mongodb.database'));
    }
}
