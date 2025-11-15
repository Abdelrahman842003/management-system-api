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
        return $this->user()->can('update-task');
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
            'depends_on_task_id.not_in' => 'A task cannot depend on itself.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $task = $this->route('task');

            if ($task instanceof Task && $task->wouldCreateCircularDependency($this->depends_on_task_id)) {
                $validator->errors()->add(
                    'depends_on_task_id',
                    'Adding this dependency would create a circular dependency.'
                );
            }
        });
    }
}
