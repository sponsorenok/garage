<?php

namespace App\Enum;

final class PartRequestCategory
{
    public const SUSPENSION = 'SUSPENSION';
    public const CLUTCH = 'CLUTCH';
    public const ENGINE = 'ENGINE';
    public const ELECTRIC = 'ELECTRIC';
    public const BRAKES = 'BRAKES';
    public const TRANSMISSION = 'TRANSMISSION';
    public const BODY = 'BODY';
    public const OTHER = 'OTHER';

    /** label => value (для ChoiceType) */
    public static function choices(): array
    {
        return [
            'Підвіска' => self::SUSPENSION,
            'Зчеплення' => self::CLUTCH,
            'Двигун' => self::ENGINE,
            'Електрика' => self::ELECTRIC,
            'Гальма' => self::BRAKES,
            'Трансмісія' => self::TRANSMISSION,
            'Кузов/кріплення' => self::BODY,
            'Інше' => self::OTHER,
        ];
    }

    public static function label(?string $value): string
    {
        $map = array_flip(self::choices()); // value => label
        return $value && isset($map[$value]) ? $map[$value] : '—';
    }
}
