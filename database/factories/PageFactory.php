<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'content' => '',
            'status' => PageStatus::DRAFT,
        ];
    }

    public function withPictures(int $count): Factory
    {
        return $this->afterCreating(function (Page $page) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                $page->addMedia(UploadedFile::fake()->image("page-{$i}.jpg"))
                    ->toMediaCollection('pictures');
            }
        });
    }

    public function published(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PageStatus::PUBLISHED,
            ];
        });
    }
}
