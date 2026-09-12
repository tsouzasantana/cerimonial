<?php

namespace Tests\Unit;

use App\Rules\CpfOrCnpj;
use PHPUnit\Framework\TestCase;

class CpfOrCnpjTest extends TestCase
{
    private function fails(string $value): bool
    {
        $failed = false;
        (new CpfOrCnpj)->validate('document', $value, function () use (&$failed) {
            $failed = true;
        });

        return $failed;
    }

    public function test_accepts_valid_cpf_formatted_and_unformatted(): void
    {
        $this->assertFalse($this->fails('529.982.247-25'));
        $this->assertFalse($this->fails('52998224725'));
    }

    public function test_accepts_valid_cnpj_formatted_and_unformatted(): void
    {
        $this->assertFalse($this->fails('11.222.333/0001-81'));
        $this->assertFalse($this->fails('11222333000181'));
    }

    public function test_rejects_cpf_with_wrong_check_digit(): void
    {
        $this->assertTrue($this->fails('529.982.247-26'));
    }

    public function test_rejects_cnpj_with_wrong_check_digit(): void
    {
        $this->assertTrue($this->fails('11.222.333/0001-82'));
    }

    public function test_rejects_repeated_digit_sequences(): void
    {
        $this->assertTrue($this->fails('111.111.111-11'));
        $this->assertTrue($this->fails('000.000.000-00'));
        $this->assertTrue($this->fails('11.111.111/1111-11'));
    }

    public function test_rejects_wrong_length(): void
    {
        $this->assertTrue($this->fails('123456'));
        $this->assertTrue($this->fails('123456789012345'));
    }

    public function test_accepts_null_and_empty_as_optional(): void
    {
        $this->assertFalse($this->fails(''));
    }
}
