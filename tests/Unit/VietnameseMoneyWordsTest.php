<?php

namespace Tests\Unit;

use App\Support\VietnameseMoneyWords;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VietnameseMoneyWordsTest extends TestCase
{
    #[DataProvider('amounts')]
    public function test_it_converts_integer_vnd_amounts_to_vietnamese_words(int $amount, string $expected): void
    {
        $this->assertSame($expected, (new VietnameseMoneyWords)->convert($amount));
    }

    /** @return iterable<string, array{int, string}> */
    public static function amounts(): iterable
    {
        yield 'zero' => [0, 'Không đồng.'];
        yield 'simple tens' => [25, 'Hai mươi lăm đồng.'];
        yield 'internal zero' => [105, 'Một trăm lẻ năm đồng.'];
        yield 'requested example' => [3_506_200, 'Ba triệu năm trăm lẻ sáu nghìn hai trăm đồng.'];
        yield 'leading zero group' => [4_000_005, 'Bốn triệu không trăm lẻ năm đồng.'];
        yield 'bill total' => [4_112_400, 'Bốn triệu một trăm mười hai nghìn bốn trăm đồng.'];
    }

    public function test_it_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new VietnameseMoneyWords)->convert(-1);
    }
}
