<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secretKey;
    private string $algorithm = 'HS256';

    public function __construct()
    {
        $this->secretKey = (string) env('JWT_SECRET_KEY');

        if ($this->secretKey === '') {
            throw new \RuntimeException('JWT_SECRET_KEY belum dikonfigurasi.');
        }
    }

    public function generateToken(array $user): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + 3600; // 1 jam

        $payload = [
            'iss' => 'event-booking-api',
            'iat' => $issuedAt,
            'exp' => $expiration,
            'sub' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        return JWT::encode(
            $payload,
            $this->secretKey,
            $this->algorithm
        );
    }

    public function decodeToken(string $token): object
    {
        return JWT::decode(
            $token,
            new Key($this->secretKey, $this->algorithm)
        );
    }
}