<?php

namespace App\Http\Requests;

use App\Concerns\PersonValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Person;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * Roles that are still in use cannot be removed.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Person $person */
            $person = $this->route('person');
            $roles = (array) $this->input('roles', []);

            if ($person->student !== null && ! in_array(UserRole::Student->value, $roles, true)) {
                $validator->errors()->add('roles', __('O papel de aluno não pode ser removido enquanto houver cadastro de aluno.'));
            }

            if ($person->teacher !== null && ! in_array(UserRole::Teacher->value, $roles, true)) {
                $validator->errors()->add('roles', __('O papel de professor não pode ser removido enquanto houver cadastro de professor.'));
            }

            if ($person->user_id === $this->user()->id && $this->user()->hasRole(UserRole::Admin->value) && ! in_array(UserRole::Admin->value, $roles, true)) {
                $validator->errors()->add('roles', __('Você não pode remover o seu próprio papel de administrador.'));
            }
        }];
    }
}
