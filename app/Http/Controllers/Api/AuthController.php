<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/register',
        summary: 'Registrasi akun baru',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Budi'),
                    new OA\Property(property: 'email', type: 'string', example: 'budi@mail.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registrasi berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Registrasi berhasil'),
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456'),
                    ]),
                ])
            ),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        $user = User::create($validated);

        return $this->success('Registrasi berhasil', [
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ], 201);
    }

    #[OA\Post(
        path: '/login',
        summary: 'Login dan dapatkan Bearer token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'budi@mail.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Login berhasil'),
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '2|abcdef123456'),
                    ]),
                ])
            ),
            new OA\Response(response: 401, description: 'Email atau password salah', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password wajib diisi',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->fail('Email atau password salah', 401);
        }

        return $this->success('Login berhasil', [
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ]);
    }

    #[OA\Get(
        path: '/profile',
        summary: 'Lihat profil pengguna yang sedang login',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil berhasil diambil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Profil berhasil diambil'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function profile(Request $request): JsonResponse
    {
        return $this->success('Profil berhasil diambil', $request->user());
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Logout (menghapus token yang sedang dipakai)',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        // Hapus token yang sedang dipakai saja (perangkat lain tetap login).
        $request->user()->currentAccessToken()->delete();

        return $this->success('Logout berhasil');
    }
}