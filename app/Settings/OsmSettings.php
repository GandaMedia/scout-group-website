<?php

namespace App\Settings;

use App\Enums\Section;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class OsmSettings extends Settings
{
    #[ShouldBeEncrypted]
    public string $refresh_token;

    #[ShouldBeEncrypted]
    public string $access_token;

    public ?string $access_token_expires_at;

    public string $squirrels_section_id;

    public string $beavers_section_id;

    public string $cubs_section_id;

    public string $scouts_section_id;

    public string $explorers_section_id;

    public string $network_section_id;

    public string $target_section_id;

    public string $target_term_id;

    public ?string $directory_account_name;

    public ?string $directory_account_email;

    public string $directory_sections;

    public string $directory_terms_by_section;

    public ?string $directory_refreshed_at;

    public ?string $directory_refresh_queued_at;

    public ?string $directory_last_error;

    public static function group(): string
    {
        return 'osm';
    }

    public function hasOauthTokens(): bool
    {
        return filled($this->refresh_token) || filled($this->access_token);
    }

    /**
     * @param  array<string, string>  $sections
     * @param  array<string, array<string, string>>  $termsBySection
     */
    public function storeDirectorySnapshot(
        ?string $accountName,
        ?string $accountEmail,
        array $sections,
        array $termsBySection,
    ): void {
        $this->directory_account_name = $accountName;
        $this->directory_account_email = $accountEmail;
        $this->directory_sections = json_encode($sections, JSON_THROW_ON_ERROR);
        $this->directory_terms_by_section = json_encode($termsBySection, JSON_THROW_ON_ERROR);
        $this->directory_refreshed_at = now()->toIso8601String();
        $this->directory_refresh_queued_at = null;
        $this->directory_last_error = null;
        $this->save();
    }

    public function markDirectoryRefreshQueued(): void
    {
        $this->directory_refresh_queued_at = now()->toIso8601String();
        $this->save();
    }

    public function markDirectoryRefreshFailed(string $message): void
    {
        $this->directory_last_error = $message;
        $this->directory_refresh_queued_at = null;
        $this->save();
    }

    public function clearDirectorySnapshot(): void
    {
        $this->directory_account_name = null;
        $this->directory_account_email = null;
        $this->directory_sections = '{}';
        $this->directory_terms_by_section = '{}';
        $this->directory_refreshed_at = null;
        $this->directory_refresh_queued_at = null;
        $this->directory_last_error = null;
        $this->save();
    }

    /**
     * @return array<string, string>
     */
    public function directorySections(): array
    {
        /** @var array<string, string> $sections */
        $sections = $this->decodeStoredArray($this->directory_sections);

        return $sections;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function directoryTermsBySection(): array
    {
        /** @var array<string, array<string, string>> $termsBySection */
        $termsBySection = $this->decodeStoredArray($this->directory_terms_by_section);

        return $termsBySection;
    }

    /**
     * @return array{sectionid: string, term_id: string}
     */
    public function waitingListMapping(): array
    {
        return [
            'sectionid' => $this->target_section_id,
            'term_id' => $this->target_term_id,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function publicSectionMappings(): array
    {
        $sectionSettings = app(SectionSettings::class);

        return collect(Section::ageOrderedCases())
            ->filter(fn (Section $section): bool => $sectionSettings->isEnabled($section))
            ->mapWithKeys(fn (Section $section): array => [
                $section->value => $this->sectionMappingProperty($section),
            ])
            ->all();
    }

    private function sectionMappingProperty(Section $section): string
    {
        /** @var string $value */
        $value = $this->{$section->slug().'_section_id'};

        return $value;
    }

    /**
     * @return array<mixed>
     */
    private function decodeStoredArray(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
