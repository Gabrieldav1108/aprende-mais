<?php

namespace App\Http\Requests;

use App\Concerns\StudentValidationRules;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    use StudentValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
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
        /** @var Student $student */
        $student = $this->route('student');

        return [
            ...$this->profileRules($student->person->user_id),
            ...$this->studentRules($student),
        ];
    }
}
