<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class Decimal
{
    public const int SCALE = 8;
    public const string FEE_RATE = '0.015';

    public static function add(string $left, string $right, int $scale = self::SCALE): string
    {
        return BigDecimal::of($left)
            ->plus($right)
            ->toScale($scale, RoundingMode::HALF_UP)
            ->__toString();
    }

    public static function sub(string $left, string $right, int $scale = self::SCALE): string
    {
        return BigDecimal::of($left)
            ->minus($right)
            ->toScale($scale, RoundingMode::HALF_UP)
            ->__toString();
    }

    public static function mul(string $left, string $right, int $scale = self::SCALE): string
    {
        return BigDecimal::of($left)
            ->multipliedBy($right)
            ->toScale($scale, RoundingMode::HALF_UP)
            ->__toString();
    }

    public static function cmp(string $left, string $right): int
    {
        return BigDecimal::of($left)->compareTo($right);
    }
}
