<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Subject::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'code')->where('course_id', $course->id)->whereNull('deleted_at'),
            ],
            'workload_hours' => ['required', 'integer', 'min:1', 'max:65535'],
            'suggested_term_number' => ['nullable', 'integer', 'min:1', 'max:'.$course->duration_terms],
        ];
    }
}
