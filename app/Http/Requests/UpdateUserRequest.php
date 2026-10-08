<?php

namespace App\Http\Requests;

use App\Concerns\PersonValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Person;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use PersonValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('person'));
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
        /** @var Person $person */
        $person = $this->route('person');

        return [
            ...$this->profileRules($person->user_id),
            ...$this->personRules($person),
        ];
    }
}
