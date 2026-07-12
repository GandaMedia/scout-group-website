<?php

namespace App\Casts;

use App\Enums\Section;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CalendarEventSectionsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<int, Section>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [];
        }

        $decodedSections = is_array($value)
            ? $value
            : json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            static fn (string|Section $section): Section => $section instanceof Section ? $section : Section::from($section),
            $decodedSections,
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, Section|string>|null  $value
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('The sections attribute must be an array.');
        }

        $sections = array_map(
            static fn (string|Section $section): Section => $section instanceof Section ? $section : Section::from($section),
            $value,
        );

        return [
            $key => json_encode(
                array_map(static fn (Section $section): string => $section->value, $sections),
                JSON_THROW_ON_ERROR,
            ),
        ];
    }
}
