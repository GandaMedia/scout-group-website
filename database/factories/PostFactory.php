<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Filament\Blocks\CtaBlock;
use App\Filament\Blocks\HeroBlock;
use App\Filament\Blocks\RichTextBlock;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst($this->faker->unique()->words(rand(3, 6), true)),
            'content' => '',
            'status' => PostStatus::DRAFT,
            'author_name' => $this->faker->name(),
            'is_password_protected' => true,
            'published_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Post $post): void {
            if ($post->pageBuilderBlocks()->exists()) {
                return;
            }

            $post->pageBuilderBlocks()->createMany([
                [
                    'block_type' => HeroBlock::class,
                    'order' => 1,
                    'data' => [
                        'eyebrow' => 'Scout Group News',
                        'title' => $post->title,
                        'body' => $this->faker->paragraph(3),
                        'primary_label' => 'Read more',
                        'primary_url' => '/news/'.$post->slug,
                    ],
                ],
                [
                    'block_type' => RichTextBlock::class,
                    'order' => 2,
                    'data' => [
                        'content' => $this->fakeRichText(),
                    ],
                ],
                [
                    'block_type' => CtaBlock::class,
                    'order' => 3,
                    'data' => [
                        'title' => 'Join the adventure',
                        'body' => $this->faker->sentence(12),
                        'button_label' => 'Contact us',
                        'button_url' => '/contact',
                    ],
                ],
            ]);
        });
    }

    public function published(): Factory
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }

    public function scheduled(): Factory
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->addDay(),
        ]);
    }

    public function unprotected(): Factory
    {
        return $this->state(fn (): array => [
            'is_password_protected' => false,
        ]);
    }

    private function fakeRichText(): string
    {
        return collect([
            '## '.$this->faker->sentence(4),
            $this->faker->paragraph(3),
            '### '.$this->faker->sentence(3),
            '- '.$this->faker->sentence(6),
            '- '.$this->faker->sentence(6),
            '- '.$this->faker->sentence(6),
        ])->implode("\n\n");
    }
}
