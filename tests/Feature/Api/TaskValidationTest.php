<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class TaskValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->manager = User::where('email', 'manager1@example.com')->first();
    }

    public function test_title_is_required(): void
    {
        $data = [
            'description' => 'Task description',
        ];

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/v1/tasks', $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('title');
    }

    public function test_status_must_be_valid_enum(): void
    {
        $task = Task::factory()->create(['created_by' => $this->manager->id]);

        $data = [
            'title' => 'Task Title',
            'status' => 'invalid-status',
        ];

        $response = $this->actingAs($this->manager, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('status');
    }

    public function test_assigned_to_must_exist(): void
    {
        $data = [
            'title' => 'Task Title',
            'assigned_to' => 99999,
        ];

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/v1/tasks', $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('assigned_to');
    }

    public function test_due_date_must_be_future_date(): void
    {
        $data = [
            'title' => 'Task Title',
            'due_date' => now()->subDays(1)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/v1/tasks', $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('due_date');
    }

    public function test_depends_on_task_id_must_exist(): void
    {
        $task = Task::factory()->create(['created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/dependencies", [
                'depends_on_task_id' => 99999,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('depends_on_task_id');
    }
}
