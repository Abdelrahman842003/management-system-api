<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');
        $user = $this->user();

        // Check if user has the permission
        \App\Helpers\ExceptionHelper::authorize(
            $user->can('update-task-status'),
            'You do not have permission to update task status.'
        );

        // Check if user is assigned to the task
        \App\Helpers\ExceptionHelper::authorize(
            $task instanceof Task && $task->assigned_to === $user->id,
            'You can only update the status of tasks assigned to you.'
        );

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'completed', 'canceled'])],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status. Allowed values: pending, completed, canceled.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->status === 'completed') {
                $task = $this->route('task');

                if ($task instanceof Task && ! $task->canBeCompleted()) {
                    $pendingDeps = $task->dependencies()
                        ->where('status', '!=', 'completed')
                        ->pluck('title')
                        ->toArray();
                    
                    $depList = !empty($pendingDeps) 
                        ? ' Pending dependencies: ' . implode(', ', $pendingDeps)
                        : '';
                    
                    $validator->errors()->add(
                        'status',
                        'Cannot complete task. Some dependencies are not yet completed.' . $depList
                    );
                }
            }
        });
    }
}
