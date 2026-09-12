<?php

namespace Database\Factories\Concerns;

/**
 * Generates CPF/CNPJ numbers with correct verification digits, so factory
 * output passes the app's real CpfOrCnpj validation rule when a test
 * resubmits it through a form request.
 */
trait GeneratesFakeDocuments
{
    protected function fakeCpf(): string
    {
        $digits = array_map(fn () => random_int(0, 9), range(1, 9));
        $digits[] = $this->modulo11CheckDigit($digits, 10);
        $digits[] = $this->modulo11CheckDigit($digits, 11);

        return sprintf(
            '%d%d%d.%d%d%d.%d%d%d-%d%d',
            ...$digits
        );
    }

    protected function fakeCnpj(): string
    {
        $digits = array_merge(array_map(fn () => random_int(0, 9), range(1, 8)), [0, 0, 0, 1]);
        $digits[] = $this->modulo11CheckDigit($digits, null, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $digits[] = $this->modulo11CheckDigit($digits, null, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return sprintf(
            '%d%d.%d%d%d.%d%d%d/%d%d%d%d-%d%d',
            ...$digits
        );
    }

    private function modulo11CheckDigit(array $digits, ?int $startWeight = null, ?array $weights = null): int
    {
        $weights ??= range($startWeight, 2);

        $sum = 0;
        foreach ($digits as $i => $digit) {
            $sum += $digit * $weights[$i];
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
