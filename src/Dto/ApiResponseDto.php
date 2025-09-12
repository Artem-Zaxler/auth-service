<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ApiResponseDto",
    description: "Standard API response format"
)]
class ApiResponseDto
{
    #[OA\Property(type: "string", example: "success", enum: ["success", "error"])]
    public string $status;

    #[OA\Property(type: "integer", example: 200)]
    public int $code;

    #[OA\Property(
        description: "Response data for successful requests",
        type: "object",
        additionalProperties: true
    )]
    public mixed $data;

    #[OA\Property(type: "string", example: "Operation completed successfully")]
    public ?string $message;

    #[OA\Property(
        type: "array",
        items: new OA\Items(
            type: "object",
            properties: [
                new OA\Property(property: "field", type: "string", example: "username"),
                new OA\Property(property: "message", type: "string", example: "This value should not be blank.")
            ]
        ),
        description: "Validation errors"
    )]
    public ?array $errors;

    #[OA\Property(type: "string", format: "date-time", example: "2025-09-12T12:00:00+00:00")]
    public string $timestamp;

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
