<?php

namespace App\Enums;

enum MealType: string
{
    case BREAKFAST = 'Breakfast';
    case LUNCH = 'Lunch';
    case DINNER = 'Dinner';
    case SNACK = 'Snack';
    case OTHER = 'Other';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mealType): array => [$mealType->value => $mealType->value])
            ->all();
    }
}
