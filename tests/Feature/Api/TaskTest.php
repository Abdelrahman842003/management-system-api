<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->manager = User::where('email', 'manager1@example.com')->first();
        $this->user = User::where('email', 'user1@example.com')->first();
    }

    public function test_manager_can_create_task(): void
    {
        $data = [
            'title' => 'New Task',
            'description' => 'Task description',
            'assigned_to' => $this->user->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/v1/tasks', $data);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment(['title' => 'New Task']);

        $this->assertDatabaseHas('tasks', ['title' => 'New Task']);
    }

    public function test_user_cannot_create_task(): void
    {
        $data = [
            'title' => 'New Task',
            'description' => 'Task description',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/tasks', $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_manager_can_list_all_tasks(): void
    {
        Task::factory()->count(5)->create();

        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/v1/tasks');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'description', 'status', 'due_date'],
                ],
            ]);
    }

    public function test_user_can_list_only_assigned_tasks(): void
    {
        Task::factory()->create(['assigned_to' => $this->user->id, 'created_by' => $this->manager->id]);
        Task::factory()->create(['assigned_to' => $this->manager->id, 'created_by' => $this->manager->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasks');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.total', 1);
    }

    public function test_can_filter_tasks_by_status(): void
    {
        Task::factory()->pending()->count(2)->create(['created_by' => $this->manager->id]);
        Task::factory()->completed()->count(3)->create(['created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/v1/tasks?status=pending');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_filter_tasks_by_assigned_user(): void
    {
        Task::factory()->count(2)->create(['assigned_to' => $this->user->id, 'created_by' => $this->manager->id]);
        Task::factory()->count(3)->create(['assigned_to' => $this->manager->id, 'created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson("/api/v1/tasks?assigned_to={$this->user->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_manager_can_update_task(): void
    {
        $task = Task::factory()->create(['created_by' => $this->manager->id]);

        $data = [
            'title' => 'Updated Title',
            'status' => 'completed',
        ];

        $response = $this->actingAs($this->manager, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}", $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    public function test_user_cannot_update_task_details(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id,
        ]);

        $data = ['title' => 'Updated Title'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}", $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_user_can_update_status_of_assigned_task(): void
    {
        $task = Task::factory()->pending()->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_user_cannot_update_status_of_unassigned_task(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->manager->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_can_delete_task(): void
    {
        $task = Task::factory()->create(['created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'message',
                'timestamp',
            ]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_can_view_task_with_dependencies(): void
    {
        $dependency = Task::factory()->completed()->create(['created_by' => $this->manager->id]);
        $task = Task::factory()->create(['created_by' => $this->manager->id]);
        $task->dependencies()->attach($dependency->id);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'dependencies' => [
                        '*' => ['id', 'title', 'status'],
                    ],
                ],
            ]);
    }
}
