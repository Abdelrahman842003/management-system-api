<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'assigned_to',
        'created_by',
        'due_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * Get the user assigned to this task.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this task.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the tasks that this task depends on.
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withTimestamps();
    }

    /**
     * Get the tasks that depend on this task.
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withTimestamps();
    }

    /**
     * Scope a query to only include tasks for a specific user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('assigned_to', $user->id);
    }

    /**
     * Check if the task can be completed.
     * A task can only be completed if all its dependencies are completed.
     */
    public function canBeCompleted(): bool
    {
        if ($this->status === 'completed') {
            return true;
        }

        $incompleteDependencies = $this->dependencies()
            ->whereNotIn('status', ['completed'])
            ->count();

        return $incompleteDependencies === 0;
    }

    /**
     * Check if adding a dependency would create a circular dependency.
     */
    public function wouldCreateCircularDependency(int $dependsOnTaskId): bool
    {
        if ($this->id === $dependsOnTaskId) {
            return true;
        }

        $dependsOnTask = Task::find($dependsOnTaskId);

        if (! $dependsOnTask) {
            return false;
        }

        // Check if the task we want to depend on already depends on this task
        return $this->isInDependencyChain($dependsOnTask);
    }

    /**
     * Recursively check if this task is in the dependency chain of another task.
     */
    private function isInDependencyChain(Task $task): bool
    {
        $dependencies = $task->dependencies;

        foreach ($dependencies as $dependency) {
            if ($dependency->id === $this->id) {
                return true;
            }

            if ($this->isInDependencyChain($dependency)) {
                return true;
            }
        }

        return false;
    }
}
