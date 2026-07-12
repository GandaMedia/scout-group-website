<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Redberry\PageBuilderPlugin\Components\Forms\PageBuilder;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                        if (filled($get('slug'))) {
                            return;
                        }

                        $set('slug', Str::slug((string) $state));
                    }),

                TextInput::make('slug')
                    ->required(fn (string $context) => $context === 'edit')
                    ->maxLength(255)
                    ->unique(Post::class, 'slug', fn ($record) => $record)
                    ->disabled(fn (?string $operation, ?Post $record) => $operation === 'edit' && $record?->status === PostStatus::PUBLISHED),

                TextInput::make('author_name')
                    ->label('Author')
                    ->required()
                    ->maxLength(255),

                Toggle::make('is_password_protected')
                    ->label('Password protected')
                    ->default(true)
                    ->helperText('Protected posts use the site-wide news password.'),

                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->native(false),

                DateTimePicker::make('published_at')
                    ->label('Publish date')
                    ->native(false)
                    ->required(fn (Get $get): bool => ($get('status') instanceof PostStatus ? $get('status')->value : $get('status')) === PostStatus::PUBLISHED->value),

                PageBuilder::make('content')
                    ->blocks(self::blocks())
                    ->selectBlockAction(fn ($action) => $action->selectField(fn (Select $field): Select => $field->native()))
                    ->reorderable()
                    ->required()
                    ->columnSpanFull(),

                Select::make('status')
                    ->default(PostStatus::DRAFT)
                    ->options(PostStatus::class)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                        $publishedValue = $state instanceof PostStatus ? $state->value : $state;

                        if ($publishedValue !== PostStatus::PUBLISHED->value || filled($get('published_at'))) {
                            return;
                        }

                        $set('published_at', now());
                    }),
            ]);
    }

    /**
     * @return array<class-string>
     */
    public static function blocks(): array
    {
        return PageForm::blocks();
    }
}
