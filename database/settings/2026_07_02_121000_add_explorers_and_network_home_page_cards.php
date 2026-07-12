<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('home_page.section_cards', function (array $cards): array {
            $cardsBySlug = collect($cards)->keyBy('page_slug');

            foreach ($this->newCards() as $card) {
                if ($cardsBySlug->has($card['page_slug'])) {
                    continue;
                }

                $cards[] = $card;
            }

            return array_values($cards);
        });
    }

    /**
     * @return list<array{section: string, age_range: string, time_slot: string, description: string, page_slug: string}>
     */
    private function newCards(): array
    {
        return [
            [
                'section' => 'Explorers',
                'age_range' => '14 - 18 years old',
                'time_slot' => 'District unit - ask for current details',
                'description' => 'Shape a youth-led programme, take on bigger challenges and build skills through adventure, service and leadership.',
                'page_slug' => 'explorers',
            ],
            [
                'section' => 'Network',
                'age_range' => '18 - 25 years old',
                'time_slot' => 'District projects and events',
                'description' => 'Create projects and events with other 18 to 25 year olds across adventure, community and international themes.',
                'page_slug' => 'network',
            ],
        ];
    }
};
