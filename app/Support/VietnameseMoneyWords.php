<?php

namespace App\Support;

use InvalidArgumentException;

class VietnameseMoneyWords
{
    /** @var list<string> */
    private const DIGITS = [
        'không',
        'một',
        'hai',
        'ba',
        'bốn',
        'năm',
        'sáu',
        'bảy',
        'tám',
        'chín',
    ];

    /** @var list<string> */
    private const SCALES = [
        '',
        'nghìn',
        'triệu',
        'tỷ',
        'nghìn tỷ',
        'triệu tỷ',
    ];

    public function convert(int $amount): string
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Số tiền không được âm.');
        }

        if ($amount === 0) {
            return 'Không đồng.';
        }

        $groups = [];
        $remaining = $amount;

        while ($remaining > 0) {
            $groups[] = $remaining % 1000;
            $remaining = intdiv($remaining, 1000);
        }

        if (count($groups) > count(self::SCALES)) {
            throw new InvalidArgumentException('Số tiền vượt quá giới hạn hỗ trợ.');
        }

        $parts = [];
        $highestGroup = count($groups) - 1;

        for ($index = $highestGroup; $index >= 0; $index--) {
            $group = $groups[$index];

            if ($group === 0) {
                continue;
            }

            $parts[] = $this->readGroup($group, $index < $highestGroup);

            if (self::SCALES[$index] !== '') {
                $parts[] = self::SCALES[$index];
            }
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1))
            .mb_substr($parts[0], 1)
            .(count($parts) > 1 ? ' '.implode(' ', array_slice($parts, 1)) : '')
            .' đồng.';
    }

    private function readGroup(int $number, bool $includeLeadingHundreds): string
    {
        $hundreds = intdiv($number, 100);
        $remainder = $number % 100;
        $tens = intdiv($remainder, 10);
        $ones = $remainder % 10;
        $words = [];

        if ($hundreds > 0 || $includeLeadingHundreds) {
            $words[] = self::DIGITS[$hundreds].' trăm';
        }

        if ($tens > 1) {
            $words[] = self::DIGITS[$tens].' mươi';
        } elseif ($tens === 1) {
            $words[] = 'mười';
        } elseif ($ones > 0 && ($hundreds > 0 || $includeLeadingHundreds)) {
            $words[] = 'lẻ';
        }

        if ($ones > 0) {
            $words[] = match (true) {
                $ones === 1 && $tens > 1 => 'mốt',
                $ones === 5 && $tens > 0 => 'lăm',
                default => self::DIGITS[$ones],
            };
        }

        return implode(' ', $words);
    }
}
