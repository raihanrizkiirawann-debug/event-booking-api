<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'event_id',
        'quantity',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'user_id'  => 'required|integer',
        'event_id' => 'required|integer',
        'quantity' => 'required|integer|greater_than[0]|less_than_equal_to[5]',
        'status'   => 'permit_empty|in_list[booked,cancelled]',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User wajib diisi.',
            'integer'  => 'User ID harus berupa angka.',
        ],
        'event_id' => [
            'required' => 'Event wajib diisi.',
            'integer'  => 'Event ID harus berupa angka.',
        ],
        'quantity' => [
            'required'            => 'Jumlah tiket wajib diisi.',
            'integer'             => 'Jumlah tiket harus berupa angka.',
            'greater_than'        => 'Jumlah tiket minimal 1.',
            'less_than_equal_to'  => 'Maksimal 5 tiket per booking.',
        ],
        'status' => [
            'in_list' => 'Status booking tidak valid.',
        ],
    ];
}