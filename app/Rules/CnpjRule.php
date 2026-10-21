<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CnpjRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(\d{14}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})$/', $value)) {
            $fail(__('O campo :attribute deve estar no formato 00.000.000/0000-00.'));

            return;
        }

        if (! self::isValid(preg_replace('/\D/', '', $value))) {
            $fail(__('O :attribute informado não é um CNPJ válido.'));
        }
    }

    /**
     * Whether the given 14 digits form a CNPJ with valid check digits.
     */
    public static function isValid(string $cnpj): bool
    {
        if (! preg_match('/^\d{14}$/', $cnpj) || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([12, 13] as $length) {
            $sum = 0;
            $weight = $length - 7;

            for ($i = 0; $i < $length; $i++) {
                $sum += (int) $cnpj[$i] * $weight;
                $weight = $weight === 2 ? 9 : $weight - 1;
            }

            $digit = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

            if ((int) $cnpj[$length] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
