<?php

namespace App\Http\Requests;

use App\Concerns\ClassroomValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomRequest extends FormRequest
{
    use ClassroomValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('classroom'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->classroomRules($this->route('classroom'));
    }
}
