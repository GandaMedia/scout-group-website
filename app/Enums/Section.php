<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum Section: string implements HasLabel
{
    case SCOUTS = 'Scouts';
    case CUBS = 'Cubs';
    case BEAVERS = 'Beavers';
    case SQUIRRELS = 'Squirrels';
    case EXPLORERS = 'Explorers';
    case NETWORK = 'Network';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function slug(): string
    {
        return Str::slug($this->value);
    }

    public function ageRangeLabel(): string
    {
        return match ($this) {
            self::SQUIRRELS => 'Ages 4-6',
            self::BEAVERS => 'Ages 6-8',
            self::CUBS => 'Ages 8-10½',
            self::SCOUTS => 'Ages 10½-14',
            self::EXPLORERS => 'Ages 14-18',
            self::NETWORK => 'Ages 18-25',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        return collect(self::cases())
            ->first(fn (self $section): bool => $section->slug() === $slug);
    }

    /**
     * @return array<string, string>
     */
    public static function optionsBySlug(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $section): array => [$section->slug() => $section->value])
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return collect(self::cases())
            ->map(fn (self $section): string => $section->slug())
            ->values()
            ->all();
    }

    /**
     * @return list<self>
     */
    public static function ageOrderedCases(): array
    {
        return [
            self::SQUIRRELS,
            self::BEAVERS,
            self::CUBS,
            self::SCOUTS,
            self::EXPLORERS,
            self::NETWORK,
        ];
    }
}
