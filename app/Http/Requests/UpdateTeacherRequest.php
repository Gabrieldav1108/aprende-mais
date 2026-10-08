<?php

namespace App\Http\Requests;

use App\Concerns\TeacherValidationRules;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherRequest extends FormRequest
{
    use TeacherValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('teacher'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizedPersonInput());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Teacher $teacher */
        $teacher = $this->route('teacher');

        return [
            ...$this->profileRules($teacher->person->user_id),
            ...$this->teacherRules($teacher),
        ];
    }
}
