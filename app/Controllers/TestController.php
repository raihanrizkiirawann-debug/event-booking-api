<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class TestController extends BaseController
{
    public function protected(): ResponseInterface
    {
        $user = $this->request->user;

        return $this->response->setJSON([
            'success' => true,
            'message' => 'JWT berhasil diverifikasi.',
            'data' => [
                'user_id' => $user->sub,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function admin(): ResponseInterface
    {
        $user = $this->request->user;

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Akses admin berhasil.',
            'data' => [
                'user_id' => $user->sub,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }
}