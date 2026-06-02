<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CampusLocation extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'campus_locations';

    protected $fillable = [
        'nama',
        'area',
        'campus_key',
    ];
}
