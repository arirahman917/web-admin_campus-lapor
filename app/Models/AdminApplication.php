<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AdminApplication extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'admin_applications';

    protected $fillable = [
        'nama',
        'nidn',
        'email',
        'unit',
        'kampus',
        'kode_kampus',
        'phone',
        'alamat_kampus',
        'role',
        'status',
        'alasan',
        'surat_tugas_nama',
        'surat_tugas_path',
        'username',
    ];
}
