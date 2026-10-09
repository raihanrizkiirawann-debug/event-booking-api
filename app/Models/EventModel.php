<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'title',
        'description',
        'event_date',
        'location',
        'quota',
        'available_quota',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'title'          => 'required|min_length[3]|max_length[150]',
        'description'    => 'permit_empty',
        'event_date'     => 'required',
        'location'       => 'required|max_length[255]',
        'quota'          => 'required|integer|greater_than[0]',
        'available_quota' => 'required|integer|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'title' => [
            'required' => 'Judul event wajib diisi.',
        ],
        'event_date' => [
            'required' => 'Tanggal event wajib diisi.',
        ],
        'location' => [
            'required' => 'Lokasi event wajib diisi.',
        ],
        'quota' => [
            'required'         => 'Kuota wajib diisi.',
            'integer'          => 'Kuota harus berupa angka.',
            'greater_than'     => 'Kuota harus lebih dari 0.',
        ],
        'available_quota' => [
            'required'              => 'Kuota tersedia wajib diisi.',
            'integer'               => 'Kuota tersedia harus berupa angka.',
            'greater_than_equal_to' => 'Kuota tersedia tidak boleh negatif.',
        ],
    ];
}