<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a document is a well-formed CPF (11 digits) or CNPJ (14
 * digits), checking the official verification-digit algorithm for each.
 * Accepts formatted ("123.456.789-09") or unformatted ("12345678909") input.
 */
class CpfOrCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        $isValid = match (strlen($digits)) {
            11 => $this->isValidCpf($digits),
            14 => $this->isValidCnpj($digits),
            default => false,
        };

        if (! $isValid) {
            $fail('O CPF ou CNPJ informado não é válido.');
        }
    }

    private function isValidCpf(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($position = 9; $position <= 10; $position++) {
            $sum = 0;
            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $cpf[$i] * (($position + 1) - $i);
            }
            $remainder = $sum % 11;
            $checkDigit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cpf[$position] !== $checkDigit) {
                return false;
            }
        }

        return true;
    }

    private function isValidCnpj(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $calculateDigit = function (string $base, array $weights) {
            $sum = 0;
            foreach (str_split($base) as $i => $digit) {
                $sum += (int) $digit * $weights[$i];
            }

            $remainder = $sum % 11;

            return $remainder < 2 ? 0 : 11 - $remainder;
        };

        $firstDigit = $calculateDigit(substr($cnpj, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        if ((int) $cnpj[12] !== $firstDigit) {
            return false;
        }

        $secondDigit = $calculateDigit(substr($cnpj, 0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return (int) $cnpj[13] === $secondDigit;
    }
}
