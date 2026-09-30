<?php

namespace App\Http\Requests;

use App\Concerns\ClassroomValidationRules;
use App\Models\Classroom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClassroomRequest extends FormRequest
{
    use ClassroomValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Classroom::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', $this->existsInSchool('courses')],
            ...$this->classroomRules(),
        ];
    }
}
