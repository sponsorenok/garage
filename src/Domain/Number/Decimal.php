<?php

namespace App\Domain\Number;

final class Decimal
{
    public static function normalize(string|int|float|null $value, int $scale): string
    {
        $s = trim((string)($value ?? '0'));
        if ($s === '') $s = '0';
        $s = str_replace(',', '.', $s);

        // allow only [-]digits[.digits]
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $s)) {
            $s = '0';
        }

        // clamp negative to 0 for qty use-cases
        if (str_starts_with($s, '-')) {
            $s = '0';
        }

        // If bcmath exists, use it for accurate rounding/format
        if (function_exists('bcadd')) {
            // Ensure dot format with exact scale (no rounding beyond scale)
            // bcadd doesn't round; it truncates extra digits. That's OK for your scale-based inputs.
            return bcadd($s, '0', $scale);
        }

        // Fallback (less safe) if bcmath not installed
        $f = (float)$s;
        if ($f < 0) $f = 0.0;
        return number_format($f, $scale, '.', '');
    }

    public static function gtZero(string|int|float|null $value): bool
    {
        $s = self::normalize($value, 6); // high precision compare
        return $s !== '0' && $s !== '0.0' && (float)$s > 0;
    }

    public static function add(string $a, string $b, int $scale): string
    {
        $a = self::normalize($a, $scale);
        $b = self::normalize($b, $scale);
        if (function_exists('bcadd')) return bcadd($a, $b, $scale);
        return number_format(((float)$a + (float)$b), $scale, '.', '');
    }
}
