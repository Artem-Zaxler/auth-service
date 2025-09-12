<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Response;

class ApiResponseDto
{
    public static function error(
        string $message,
        int $code = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        ?string $timestamp = null
    ): array {
        return [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => $timestamp ?? (new \DateTime())->format('Y-m-d\TH:i:sP')
        ];
    }

    public static function success(
        $data,
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        ?string $timestamp = null
    ): array {
        return [
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => $timestamp ?? (new \DateTime())->format('Y-m-d\TH:i:sP')
        ];
    }
}
