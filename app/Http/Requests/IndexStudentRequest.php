<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\StudentStatus;
use Illuminate\Validation\Rule;

class IndexStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
        public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'status' => ['nullable', Rule::enum(StudentStatus::class)],
            'sort_by' => ['nullable', Rule::in(['name', 'enrollment_number', 'course', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ];
    }

}
