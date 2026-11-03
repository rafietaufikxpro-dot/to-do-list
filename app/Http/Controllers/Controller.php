<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API To-Do List Duo',
    description: 'API To-Do List dengan autentikasi Laravel Sanctum. Setiap pengguna hanya dapat mengakses tugas miliknya sendiri.'
)]
#[OA\Server(url: 'http://localhost:8000/api', description: 'Local')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    description: 'Masukkan token hasil login/register (tanpa kata "Bearer").'
)]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Budi'),
        new OA\Property(property: 'email', type: 'string', example: 'budi@mail.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Task',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Mengerjakan tugas Laravel'),
        new OA\Property(property: 'deadline', type: 'string', format: 'date', nullable: true, example: '2026-11-05'),
        new OA\Property(property: 'status', type: 'string', enum: ['todo', 'done'], example: 'todo'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Validasi gagal'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['title' => ['Judul tugas wajib diisi']]
        ),
    ]
)]
abstract class Controller
{
    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $body = ['success' => true, 'message' => $message];

        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $status);
    }

    protected function fail(string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);
    }
}