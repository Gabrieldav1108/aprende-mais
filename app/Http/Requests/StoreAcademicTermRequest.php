<?php

namespace App\Http\Requests;

use App\Concerns\AcademicTermValidationRules;
use App\Models\AcademicTerm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicTermRequest extends FormRequest
{
    use AcademicTermValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', AcademicTerm::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->academicTermRules();
    }
}
