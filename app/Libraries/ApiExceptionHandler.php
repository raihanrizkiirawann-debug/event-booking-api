<?php

namespace App\Libraries;

use CodeIgniter\Debug\BaseExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;
use App\Libraries\ApiLogger;

class ApiExceptionHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode
    ): void {
        // Pastikan status code valid
        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

       ApiLogger::error(
    'Unhandled API exception',
    [
        'exception' => $exception::class,
        'message'   => $exception->getMessage(),
        'file'      => $exception->getFile(),
        'line'      => $exception->getLine(),
        'status'    => $statusCode,
    ]
);

        // Response API tidak membocorkan detail exception
        $response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'success' => false,
                'message' => $statusCode >= 500
                    ? 'Terjadi kesalahan pada server.'
                    : 'Terjadi kesalahan pada request.',
            ], JSON_UNESCAPED_UNICODE));

        $response->send();

        exit($exitCode);
    }
}