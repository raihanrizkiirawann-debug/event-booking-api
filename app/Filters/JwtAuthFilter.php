<?php

namespace App\Filters;

use App\Services\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\IncomingRequest;

class JwtAuthFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        if (!$request instanceof IncomingRequest) {
            return;
        }

        $header = $request->getHeaderLine('Authorization');

        if ($header === '') {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Token tidak ditemukan.',
                ]);
        }

        if (!preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Format token tidak valid.',
                ]);
        }

        $token = trim($matches[1]);

        try {
            $jwtService = new JwtService();
            $payload = $jwtService->decodeToken($token);

            $request->user = $payload;
        } catch (\Throwable $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Token tidak valid atau sudah kedaluwarsa.',
                ]);
        }
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
    }
}