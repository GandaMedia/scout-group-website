<?php

namespace App\Filament\Blocks\Concerns;

trait ResolvesBlockImageUrl
{
    protected static function resolveBlockImageUrl(array|string|null $image): ?string
    {
        if (blank($image)) {
            return null;
        }

        if (is_string($image)) {
            if (str($image)->startsWith(['http://', 'https://', '/'])) {
                return $image;
            }

            return static::generatedStorageUrl($image);
        }

        return static::getUrlForFile($image);
    }
}
