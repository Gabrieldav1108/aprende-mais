<?php

namespace App\Http\Requests;

use App\Concerns\CourseValidationRules;
use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    use CourseValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Course::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->courseRules();
    }
}
