<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachDependencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        \App\Helpers\ExceptionHelper::authorize(
            $this->user()->can('update-task'),
            'You do not have permission to modify task dependencies.'
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
        $task = $this->route('task');

        return [
            'depends_on_task_id' => [
                'required',
                'exists:tasks,id',
                Rule::notIn([$task?->id]),
            ],
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
            'depends_on_task_id.required' => 'The task dependency ID is required.',
            'depends_on_task_id.exists' => 'The selected task (ID: :input) does not exist. Please create the task first or use a valid task ID.',
            'depends_on_task_id.not_in' => 'A task cannot depend on itself.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'depends_on_task_id' => 'task dependency',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $task = $this->route('task');

            // Check for circular dependency
            if ($task instanceof Task && $this->depends_on_task_id) {
                if ($task->wouldCreateCircularDependency($this->depends_on_task_id)) {
                    $validator->errors()->add(
                        'depends_on_task_id',
                        'Adding this dependency would create a circular dependency chain. Task cannot depend on itself indirectly.'
                    );
                }

                // Check if dependency already exists
                if ($task->dependencies()->where('depends_on_task_id', $this->depends_on_task_id)->exists()) {
                    $validator->errors()->add(
                        'depends_on_task_id',
                        'This dependency already exists. Task ' . $task->id . ' already depends on Task ' . $this->depends_on_task_id . '.'
                    );
                }
            }
        });
    }
}
