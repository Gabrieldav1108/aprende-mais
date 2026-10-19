<?php

namespace App\Http\Requests;

use App\Concerns\PersonValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Person;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use PersonValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Person::class);
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
        return [
            ...$this->profileRules(),
            ...$this->personRules(),
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::enum(UserRole::class)],
        ];
    }
}
