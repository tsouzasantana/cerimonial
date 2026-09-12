<?php

namespace Tests\Unit;

use App\Rules\BrazilianPhoneNumber;
use PHPUnit\Framework\TestCase;

class BrazilianPhoneNumberTest extends TestCase
{
    private function fails(string $value): bool
    {
        $failed = false;
        (new BrazilianPhoneNumber)->validate('phone', $value, function () use (&$failed) {
            $failed = true;
        });

        return $failed;
    }

    public function test_accepts_valid_mobile_number(): void
    {
        $this->assertFalse($this->fails('(11) 98765-4321'));
        $this->assertFalse($this->fails('11987654321'));
    }

    public function test_accepts_valid_landline_number(): void
    {
        $this->assertFalse($this->fails('(11) 3456-7890'));
        $this->assertFalse($this->fails('1134567890'));
    }

    public function test_rejects_mobile_number_missing_leading_nine(): void
    {
        $this->assertTrue($this->fails('(11) 12345-6789'));
    }

    public function test_rejects_invalid_ddd(): void
    {
        $this->assertTrue($this->fails('(00) 98765-4321'));
        $this->assertTrue($this->fails('(01) 3456-7890'));
    }

    public function test_rejects_wrong_length(): void
    {
        $this->assertTrue($this->fails('123'));
        $this->assertTrue($this->fails('119876543210'));
    }

    public function test_accepts_empty_as_optional(): void
    {
        $this->assertFalse($this->fails(''));
    }
}
