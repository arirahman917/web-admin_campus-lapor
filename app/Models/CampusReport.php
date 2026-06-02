<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CampusReport extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'campus_reports';

    protected $fillable = [
        'reporter_id',
        'reporter_name',
        'category',
        'title',
        'location',
        'tag',
        'description',
        'photo_data',
        'status',
        'campus_key',
    ];
}
