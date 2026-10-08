<?php

namespace App\Concerns;

use App\Models\Person;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PersonValidationRules
{
    /**
     * Address fields that must all be present once any of them is filled.
     *
     * @var list<string>
     */
    private const REQUIRED_ADDRESS_FIELDS = ['zip_code', 'street', 'neighborhood', 'city', 'state', 'number'];

    /**
     * Validation rules for the personal data shared by every role.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function personRules(?Person $person = null): array
    {
        $schoolId = $person?->school_id ?? $this->user()->school_id;

        return [
            'cpf_cnpj' => [
                'nullable', 'string', 'regex:/^(\d{11}|\d{14})$/',
                Rule::unique('people', 'cpf_cnpj')
                    ->where('school_id', $schoolId)
                    ->whereNull('deleted_at')
                    ->ignore($person?->id),
            ],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'array'],
            'address.zip_code' => [...$this->requiredWithAddress('zip_code'), 'digits:8'],
            'address.street' => [...$this->requiredWithAddress('street'), 'max:255'],
            'address.neighborhood' => [...$this->requiredWithAddress('neighborhood'), 'max:100'],
            'address.city' => [...$this->requiredWithAddress('city'), 'max:100'],
            'address.state' => [...$this->requiredWithAddress('state'), 'size:2'],
            'address.number' => [...$this->requiredWithAddress('number'), 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:100'],
            'phones' => ['nullable', 'array', 'max:5'],
            'phones.*' => ['required', 'string', 'digits_between:10,13'],
            'emails' => ['nullable', 'array', 'max:5'],
            'emails.*' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * Normalize masked input (document, zip code, phones) to the digits that are stored.
     *
     * @return array<string, mixed>
     */
    protected function normalizedPersonInput(): array
    {
        $normalized = [];

        if (is_string($this->input('cpf_cnpj'))) {
            $normalized['cpf_cnpj'] = preg_replace('/\D/', '', $this->input('cpf_cnpj')) ?: null;
        }

        if (is_array($this->input('address'))) {
            $address = $this->input('address');
            $address['zip_code'] = is_string($address['zip_code'] ?? null)
                ? (preg_replace('/\D/', '', $address['zip_code']) ?: null)
                : null;
            $address['state'] = is_string($address['state'] ?? null) ? strtoupper($address['state']) : null;
            $normalized['address'] = $address;
        }

        if (is_array($this->input('phones'))) {
            $normalized['phones'] = array_values(array_filter(array_map(
                fn ($phone) => is_string($phone) ? preg_replace('/\D/', '', $phone) : null,
                $this->input('phones'),
            )));
        }

        if (is_array($this->input('emails'))) {
            $normalized['emails'] = array_values(array_filter(array_map(
                fn ($email) => is_string($email) ? trim($email) : null,
                $this->input('emails'),
            )));
        }

        return $normalized;
    }

    /**
     * An address field is required whenever any other address field is filled.
     *
     * @return list<string>
     */
    private function requiredWithAddress(string $field): array
    {
        $others = array_map(
            fn (string $other) => "address.{$other}",
            [...array_diff(self::REQUIRED_ADDRESS_FIELDS, [$field]), 'complement'],
        );

        return ['nullable', 'string', 'required_with:'.implode(',', $others)];
    }
}
