<?php

namespace App\Http\Requests;

use App\Models\ClassroomSubject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassroomSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ClassroomSubject::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $classroom = $this->route('classroom');

        return [
            'subject_id' => [
                'required', 'integer',
                Rule::exists('subjects', 'id')->where('course_id', $classroom->course_id)->whereNull('deleted_at'),
                Rule::unique('classroom_subjects', 'subject_id')->where('classroom_id', $classroom->id)->whereNull('deleted_at'),
            ],
            'teacher_id' => ['required', 'integer', Rule::exists('teachers', 'id')->whereNull('deleted_at')],
        ];
    }
}
