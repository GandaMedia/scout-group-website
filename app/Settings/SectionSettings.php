<?php

namespace App\Settings;

use App\Enums\Section;
use Spatie\LaravelSettings\Settings;

class SectionSettings extends Settings
{
    /**
     * @var list<string>
     */
    public array $enabled_section_slugs;

    public static function group(): string
    {
        return 'sections';
    }

    public function isEnabled(Section $section): bool
    {
        return in_array($section->slug(), $this->enabledSectionSlugs(), true);
    }

    /**
     * @return list<string>
     */
    public function enabledSectionSlugs(): array
    {
        $validSlugs = Section::slugs();

        return collect($this->enabled_section_slugs)
            ->filter(fn (mixed $slug): bool => is_string($slug) && in_array($slug, $validSlugs, true))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function enabledOptionsBySlug(): array
    {
        return collect(Section::cases())
            ->filter(fn (Section $section): bool => $this->isEnabled($section))
            ->mapWithKeys(fn (Section $section): array => [$section->slug() => $section->value])
            ->all();
    }
}
