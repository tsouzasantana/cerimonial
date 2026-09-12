<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a Brazilian phone number: DDD (2 digits, 11-99) followed by
 * either an 8-digit landline number or a 9-digit mobile number (which must
 * start with 9, per the post-2016 nine-digit mobile numbering plan).
 * Accepts formatted ("(11) 98765-4321") or raw digit input.
 */
class BrazilianPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        if (! in_array(strlen($digits), [10, 11], true)) {
            $fail('O telefone deve ter DDD + 8 ou 9 dígitos.');

            return;
        }

        $ddd = (int) substr($digits, 0, 2);
        if ($ddd < 11 || $ddd > 99) {
            $fail('O telefone informado tem um DDD inválido.');

            return;
        }

        if (strlen($digits) === 11 && $digits[2] !== '9') {
            $fail('O celular deve começar com 9 após o DDD.');
        }
    }
}
