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

        // User must be assigned to the task to update its status
        return $task instanceof Task
            && $task->assigned_to === $this->user()->id
            && $this->user()->can('update-task-status');
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
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->status === 'completed') {
                $task = $this->route('task');

                if ($task instanceof Task && ! $task->canBeCompleted()) {
                    $validator->errors()->add(
                        'status',
                        'Cannot complete task. Some dependencies are not yet completed.'
                    );
                }
            }
        });
    }
}
