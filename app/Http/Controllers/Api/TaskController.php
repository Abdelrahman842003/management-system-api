<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachDependencyRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskCollection;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly TaskService $taskService
    ) {}

    /**
     * Display a listing of tasks with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'assigned_to', 'due_date_from', 'due_date_to']);

        $tasks = $this->taskService->getAll($filters, $request->user());

        $collection = new TaskCollection(TaskResource::collection($tasks));
        $collectionData = $collection->toArray($request);

        return $this->successResponse(
            $collectionData['data'] ?? [],
            'Tasks retrieved successfully',
            Response::HTTP_OK,
            $collectionData['meta'] ?? null
        );
    }

    /**
     * Store a newly created task.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create(
            $request->validated(),
            $request->user()
        );

        $task->load(['assignedTo', 'createdBy', 'dependencies']);

        return $this->successResponse(
            new TaskResource($task),
            'Task created successfully',
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified task with dependencies.
     */
    public function show(Task $task): JsonResponse
    {
        // Check authorization
        $user = request()->user();

        if (! $user->can('view-all-tasks') && $task->assigned_to !== $user->id) {
            return $this->errorResponse(
                'You are not authorized to view this task.',
                null,
                Response::HTTP_FORBIDDEN
            );
        }

        $task->load(['assignedTo', 'createdBy', 'dependencies']);

        return $this->successResponse(
            new TaskResource($task),
            'Task retrieved successfully'
        );
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->update($task, $request->validated());

        return $this->successResponse(
            new TaskResource($task),
            'Task updated successfully'
        );
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): JsonResponse
    {
        // Check authorization
        if (! request()->user()->can('update-task')) {
            return $this->errorResponse(
                'You are not authorized to delete this task.',
                null,
                Response::HTTP_FORBIDDEN
            );
        }

        $this->taskService->delete($task);

        return $this->successResponse(
            null,
            'Task deleted successfully',
            Response::HTTP_OK
        );
    }

    /**
     * Update only the task status (for regular users).
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->updateStatus($task, $request->validated()['status']);

        return $this->successResponse(
            new TaskResource($task),
            'Task status updated successfully'
        );
    }

    /**
     * Attach a dependency to the task.
     */
    public function attachDependency(AttachDependencyRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->attachDependency(
            $task,
            $request->validated()['depends_on_task_id']
        );

        return $this->successResponse(
            new TaskResource($task),
            'Task dependency added successfully'
        );
    }

    /**
     * Detach a dependency from the task.
     */
    public function detachDependency(Request $request, Task $task, Task $dependsOnTask): JsonResponse
    {
        // Check authorization
        if (! $request->user()->can('update-task')) {
            return $this->errorResponse(
                'You are not authorized to modify task dependencies.',
                null,
                Response::HTTP_FORBIDDEN
            );
        }

        $task = $this->taskService->detachDependency($task, $dependsOnTask->id);

        return $this->successResponse(
            new TaskResource($task),
            'Task dependency removed successfully'
        );
    }
}
