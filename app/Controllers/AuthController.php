<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\JwtService;
use App\Services\LoginRateLimiter;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function register(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'message' => 'Request body tidak valid.',
                ]);
        }

        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Nama, email, dan password wajib diisi.',
                ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Format email tidak valid.',
                ]);
        }

        if (strlen($password) < 6) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Password minimal 6 karakter.',
                ]);
        }

        $existingUser = $this->userModel
            ->where('email', $email)
            ->first();

        if ($existingUser) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'success' => false,
                    'message' => 'Email sudah terdaftar.',
                ]);
        }

        $userId = $this->userModel->insert([
            'name'     => $name,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role'     => 'user',
        ]);

        if ($userId === false) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Gagal membuat akun.',
                    'errors'  => $this->userModel->errors(),
                ]);
        }

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'success' => true,
                'message' => 'Registrasi berhasil.',
                'data'    => [
                    'id'    => $userId,
                    'name'  => $name,
                    'email' => $email,
                    'role'  => 'user',
                ],
            ]);
    }

    public function login(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'message' => 'Request body tidak valid.',
                ]);
        }

        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Email dan password wajib diisi.',
                ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Format email tidak valid.',
                ]);
        }

        $rateLimiter = new LoginRateLimiter();

        /*
         * Rate limit berdasarkan IP + email.
         * Ini membantu mencegah brute-force login
         * tanpa memblokir seluruh user dari satu IP.
         */
        $ipAddress = $this->request->getIPAddress();

        $rateLimitKey = 'login_attempts_' . hash(
            'sha256',
            $ipAddress . '|' . $email
        );

        if ($rateLimiter->isBlocked($rateLimitKey)) {
            return $this->response
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.',
                ]);
        }

        $user = $this->userModel
            ->where('email', $email)
            ->first();

        if (!$user || !password_verify($password, $user['password'])) {
            $attempts = $rateLimiter->hit($rateLimitKey);

            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Email atau password salah.',
                    'remaining_attempts' => max(0, 5 - $attempts),
                ]);
        }

        // Login berhasil, reset counter percobaan gagal.
        $rateLimiter->clear($rateLimitKey);

        $jwtService = new JwtService();
        $token = $jwtService->generateToken($user);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'message' => 'Login berhasil.',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id'    => $user['id'],
                        'name'  => $user['name'],
                        'email' => $user['email'],
                        'role'  => $user['role'],
                    ],
                ],
            ]);
    }
}