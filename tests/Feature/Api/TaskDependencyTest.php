<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class TaskDependencyTest extends TestCase
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

    public function test_manager_can_attach_dependency(): void
    {
        $task = Task::factory()->create(['created_by' => $this->manager->id]);
        $dependencyTask = Task::factory()->create(['created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/dependencies", [
                'depends_on_task_id' => $dependencyTask->id,
            ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('task_dependencies', [
            'task_id' => $task->id,
            'depends_on_task_id' => $dependencyTask->id,
        ]);
    }

    public function test_user_cannot_attach_dependency(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id,
        ]);
        $dependencyTask = Task::factory()->create(['created_by' => $this->manager->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/dependencies", [
                'depends_on_task_id' => $dependencyTask->id,
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_can_detach_dependency(): void
    {
        $task = Task::factory()->create(['created_by' => $this->manager->id]);
        $dependencyTask = Task::factory()->create(['created_by' => $this->manager->id]);
        $task->dependencies()->attach($dependencyTask->id);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->deleteJson("/api/v1/tasks/{$task->id}/dependencies/{$dependencyTask->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing('task_dependencies', [
            'task_id' => $task->id,
            'depends_on_task_id' => $dependencyTask->id,
        ]);
    }

    public function test_cannot_create_circular_dependency(): void
    {
        $task1 = Task::factory()->create(['created_by' => $this->manager->id]);
        $task2 = Task::factory()->create(['created_by' => $this->manager->id]);

        // Task1 depends on Task2
        $task1->dependencies()->attach($task2->id);

        // Try to make Task2 depend on Task1 (circular)
        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v1/tasks/{$task2->id}/dependencies", [
                'depends_on_task_id' => $task1->id,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('depends_on_task_id');
    }

    public function test_prevents_completing_task_with_pending_dependencies(): void
    {
        $dependency = Task::factory()->pending()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->user->id,
        ]);

        $task = Task::factory()->pending()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->user->id,
        ]);

        $task->dependencies()->attach($dependency->id);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('status');
    }

    public function test_allows_completing_task_when_all_dependencies_completed(): void
    {
        $dependency = Task::factory()->completed()->create([
            'created_by' => $this->manager->id,
        ]);

        $task = Task::factory()->pending()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->user->id,
        ]);

        $task->dependencies()->attach($dependency->id);

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

    public function test_cannot_depend_on_itself(): void
    {
        $task = Task::factory()->create(['created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/dependencies", [
                'depends_on_task_id' => $task->id,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('depends_on_task_id');
    }
}
