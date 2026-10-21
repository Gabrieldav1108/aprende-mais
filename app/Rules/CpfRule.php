<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CpfRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(\d{11}|\d{3}\.\d{3}\.\d{3}-\d{2})$/', $value)) {
            $fail(__('O campo :attribute deve estar no formato 000.000.000-00.'));

            return;
        }

        if (! self::isValid(preg_replace('/\D/', '', $value))) {
            $fail(__('O :attribute informado não é um CPF válido.'));
        }
    }

    /**
     * Whether the given 11 digits form a CPF with valid check digits.
     */
    public static function isValid(string $cpf): bool
    {
        if (! preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        foreach ([9, 10] as $length) {
            $sum = 0;

            for ($i = 0; $i < $length; $i++) {
                $sum += (int) $cpf[$i] * ($length + 1 - $i);
            }

            if ((int) $cpf[$length] !== $sum * 10 % 11 % 10) {
                return false;
            }
        }

        return true;
    }
}
