<?php

namespace Tests\Unit;

use App\Rules\Rut;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RutTest extends TestCase
{
    public static function ruts(): array
    {
        return [
            'with dots' => ['12.345.678-5', true],
            'without dash' => ['123456785', true],
            'check digit K lowercase' => ['10.000.013-k', true],
            'check digit 0' => ['11.111.117-0', true],
            'seven digits' => ['1.000.005-K', true],
            'known valid' => ['11.111.111-1', true],
            'wrong check digit' => ['12.345.678-9', false],
            'letters' => ['abc', false],
            'too short' => ['1-9', false],
        ];
    }

    #[DataProvider('ruts')]
    public function test_validates_check_digit(string $rut, bool $valid): void
    {
        $this->assertSame($valid, Validator::make(['rut' => $rut], ['rut' => [new Rut]])->passes());
    }

    public function test_computes_check_digits(): void
    {
        $this->assertSame('5', Rut::checkDigit('12345678'));
        $this->assertSame('K', Rut::checkDigit('10000013'));
        $this->assertSame('0', Rut::checkDigit('11111117'));
        $this->assertSame('1', Rut::checkDigit('11111111'));
    }

    public function test_normalizes_format(): void
    {
        $this->assertSame('12345678-K', Rut::normalize(' 12.345.678-k '));
    }
}
