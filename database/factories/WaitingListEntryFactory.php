<?php

namespace Database\Factories;

use App\Enums\Section;
use App\Enums\WaitingListEntrySyncStatus;
use App\Models\WaitingListEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitingListEntry>
 */
class WaitingListEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $section = $this->faker->randomElement(Section::cases());

        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'date_of_birth' => $this->faker->dateTimeBetween('-13 years', '-4 years')->format('Y-m-d'),
            'section_slug' => $section->slug(),
            'parent_name' => $this->faker->name(),
            'parent_email' => $this->faker->safeEmail(),
            'parent_phone' => $this->faker->phoneNumber(),
            'postcode' => 'BN9 9AA',
            'notes' => $this->faker->sentence(),
            'is_possible_duplicate' => false,
            'sync_status' => WaitingListEntrySyncStatus::PENDING,
            'sync_attempts' => 0,
            'submitted_at' => now(),
        ];
    }

    public function heldDuplicate(): static
    {
        return $this->state(fn (): array => [
            'is_possible_duplicate' => true,
            'duplicate_reason' => 'Potential duplicate waiting-list request.',
            'duplicate_detected_at' => now(),
            'sync_status' => WaitingListEntrySyncStatus::HELD_DUPLICATE,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'sync_status' => WaitingListEntrySyncStatus::FAILED,
            'sync_attempts' => 1,
            'sync_attempted_at' => now(),
            'last_error' => 'OSM rejected the request.',
            'last_error_at' => now(),
        ]);
    }

    public function synced(): static
    {
        return $this->state(fn (): array => [
            'sync_status' => WaitingListEntrySyncStatus::SYNCED,
            'sync_attempts' => 1,
            'sync_attempted_at' => now(),
            'synced_at' => now(),
            'osm_scout_id' => 3100005,
            'osm_response' => [
                'result' => 'ok',
                'out_of_term' => false,
                'scoutid' => 3100005,
            ],
        ]);
    }
}
