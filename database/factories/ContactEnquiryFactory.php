<?php

namespace Database\Factories;

use App\Models\ContactEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactEnquiry>
 */
class ContactEnquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'message' => fake()->paragraphs(2, true),
            'submitted_at' => now(),
            'reviewed_at' => null,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (): array => [
            'reviewed_at' => now(),
        ]);
    }
}
