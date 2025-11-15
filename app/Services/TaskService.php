<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Get all tasks with optional filtering and authorization.
     */
    public function getAll(array $filters, User $user): Collection
    {
        $query = Task::with(['assignedTo', 'createdBy']);

        // Authorization: Regular users can only see their assigned tasks
        if (! $user->can('view-all-tasks')) {
            $query->forUser($user);
        }

        // Apply filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        return $query->latest()->get();
    }

    /**
     * Create a new task.
     */
    public function create(array $data, User $creator): Task
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['created_by'] = $creator->id;

            $task = Task::create($data);

            return $task->load(['assignedTo', 'createdBy']);
        });
    }

    /**
     * Update an existing task.
     */
    public function update(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $task->update($data);

            return $task->fresh(['assignedTo', 'createdBy', 'dependencies']);
        });
    }

    /**
     * Update only the task status.
     */
    public function updateStatus(Task $task, string $status): Task
    {
        return DB::transaction(function () use ($task, $status) {
            $task->update(['status' => $status]);

            return $task->fresh(['assignedTo', 'createdBy']);
        });
    }

    /**
     * Delete a task.
     */
    public function delete(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            return $task->delete();
        });
    }

    /**
     * Attach a dependency to a task.
     */
    public function attachDependency(Task $task, int $dependsOnTaskId): Task
    {
        return DB::transaction(function () use ($task, $dependsOnTaskId) {
            $task->dependencies()->syncWithoutDetaching([$dependsOnTaskId]);

            return $task->fresh(['dependencies']);
        });
    }

    /**
     * Detach a dependency from a task.
     */
    public function detachDependency(Task $task, int $dependsOnTaskId): Task
    {
        return DB::transaction(function () use ($task, $dependsOnTaskId) {
            $task->dependencies()->detach($dependsOnTaskId);

            return $task->fresh(['dependencies']);
        });
    }

    /**
     * Find a task by ID with all relationships.
     */
    public function findById(int $id): ?Task
    {
        return Task::with(['assignedTo', 'createdBy', 'dependencies'])
            ->find($id);
    }
}
