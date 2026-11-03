<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class TaskController extends Controller
{
    #[OA\Get(
        path: '/tasks',
        summary: 'Daftar tugas milik pengguna yang login',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Filter berdasarkan status',
                schema: new OA\Schema(type: 'string', enum: ['todo', 'done'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar tugas berhasil diambil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Daftar tugas berhasil diambil'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Task')),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Nilai status tidak valid', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Task::STATUSES)],
        ], $this->messages());

        $tasks = $request->user()->tasks()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return $this->success('Daftar tugas berhasil diambil', $tasks);
    }

    #[OA\Post(
        path: '/tasks',
        summary: 'Buat tugas baru',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Belajar API Laravel'),
                    new OA\Property(property: 'deadline', type: 'string', format: 'date', nullable: true, example: '2026-11-06'),
                    new OA\Property(property: 'status', type: 'string', enum: ['todo', 'done'], example: 'todo'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tugas berhasil dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Tugas berhasil dibuat'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Task'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'deadline' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(Task::STATUSES)],
        ], $this->messages());

        $task = $request->user()->tasks()->create($validated);

        return $this->success('Tugas berhasil dibuat', $task->refresh(), 201);
    }

    #[OA\Get(
        path: '/tasks/{id}',
        summary: 'Detail tugas',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail tugas berhasil diambil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Detail tugas berhasil diambil'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Task'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tugas tidak ditemukan (atau bukan milik Anda)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        $task = $this->findOwnTask($request, $id);

        if (! $task) {
            return $this->fail('Tugas tidak ditemukan', 404);
        }

        return $this->success('Detail tugas berhasil diambil', $task);
    }

    #[OA\Put(
        path: '/tasks/{id}',
        summary: 'Ubah tugas (judul, deadline, dan/atau status todo/done)',
        description: 'Semua field bersifat opsional. PATCH /tasks/{id} juga didukung dengan perilaku yang sama, misalnya untuk tombol selesai/belum selesai: kirim {"status":"done"}.',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Belajar API Laravel'),
                    new OA\Property(property: 'deadline', type: 'string', format: 'date', nullable: true, example: '2026-11-07'),
                    new OA\Property(property: 'status', type: 'string', enum: ['todo', 'done'], example: 'done'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tugas berhasil diperbarui',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Tugas berhasil diperbarui'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Task'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tugas tidak ditemukan (atau bukan milik Anda)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $task = $this->findOwnTask($request, $id);

        if (! $task) {
            return $this->fail('Tugas tidak ditemukan', 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'deadline' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'required', Rule::in(Task::STATUSES)],
        ], $this->messages());

        $task->update($validated);

        return $this->success('Tugas berhasil diperbarui', $task);
    }

    #[OA\Delete(
        path: '/tasks/{id}',
        summary: 'Hapus tugas',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tugas berhasil dihapus',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Tugas berhasil dihapus'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tugas tidak ditemukan (atau bukan milik Anda)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = $this->findOwnTask($request, $id);

        if (! $task) {
            return $this->fail('Tugas tidak ditemukan', 404);
        }

        $task->delete();

        return $this->success('Tugas berhasil dihapus');
    }

    private function findOwnTask(Request $request, string $id): ?Task
    {
        return $request->user()->tasks()->whereKey($id)->first();
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Judul tugas wajib diisi',
            'title.max' => 'Judul tugas maksimal 255 karakter',
            'deadline.date_format' => 'Deadline harus berformat YYYY-MM-DD',
            'status.in' => 'Status harus todo atau done',
            'status.required' => 'Status tidak boleh kosong',
        ];
    }
}