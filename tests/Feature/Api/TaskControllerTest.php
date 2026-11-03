<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertUnauthorized();
    }

    public function test_lists_only_the_authenticated_users_tasks_for_a_valid_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $doneTask = $this->createTask($user, ['status' => Task::STATUS_DONE]);
        $this->createTask($user, ['status' => Task::STATUS_TODO]);
        $this->createTask($otherUser, ['status' => Task::STATUS_DONE]);
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->getJson('/api/tasks?status=done');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $doneTask->id);
    }

    public function test_returns_422_when_task_list_status_is_invalid(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->getJson('/api/tasks?status=pending');

        $response->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Status harus todo atau done');
    }

    public function test_creates_a_task_with_the_authenticated_owner_and_default_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Finish backend API',
            'deadline' => '2026-11-06',
            'user_id' => $otherUser->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Finish backend API')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', Task::STATUS_TODO);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Finish backend API',
            'deadline' => '2026-11-06',
            'status' => Task::STATUS_TODO,
            'user_id' => $user->id,
        ]);
    }

    public function test_rejects_task_creation_with_invalid_fields(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->postJson('/api/tasks', [
            'title' => '',
            'deadline' => 'not-a-date',
            'status' => 'pending',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.title.0', 'Judul tugas wajib diisi')
            ->assertJsonPath('errors.deadline.0', 'Deadline harus berformat YYYY-MM-DD')
            ->assertJsonPath('errors.status.0', 'Status harus todo atau done');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_shows_a_task_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask($user);
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', $task->title);
    }

    public function test_returns_404_when_showing_another_users_task(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask(User::factory()->create());
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertNotFound()
            ->assertJsonPath('message', 'Tugas tidak ditemukan');
    }

    public function test_updates_a_task_with_a_partial_payload(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask($user);
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->patchJson("/api/tasks/{$task->id}", [
            'status' => Task::STATUS_DONE,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', Task::STATUS_DONE);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_DONE,
        ]);
    }

    public function test_rejects_an_update_with_an_empty_title(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask($user);
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.title.0', 'Judul tugas wajib diisi');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $task->title,
        ]);
    }

    public function test_returns_404_when_updating_another_users_task(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask(User::factory()->create());
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Changed by another user',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $task->title,
        ]);
    }

    public function test_deletes_a_task_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask($user);
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Tugas berhasil dihapus');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_returns_404_when_deleting_another_users_task(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask(User::factory()->create());
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createTask(User $user, array $attributes = []): Task
    {
        return $user->tasks()->create([
            'title' => 'Review assignment',
            'status' => Task::STATUS_TODO,
            ...$attributes,
        ]);
    }
}
