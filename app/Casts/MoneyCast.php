<?php

namespace App\Casts;

use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    public function __construct(
        private readonly string $currencyCode = 'GBP',
        private readonly ?string $currencyAttribute = null,
    ) {}

    /**
     * Cast the stored minor amount to a Money instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::ofMinor($value, $this->currencyCode($attributes));
    }

    /**
     * Store a Money instance as its minor amount.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return $this->withCurrency($key, null, null);
        }

        if ($value instanceof Money) {
            return $this->withCurrency(
                $key,
                $value->getMinorAmount()->toInt(),
                $value->getCurrency()->getCurrencyCode(),
            );
        }

        if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) {
            return $this->withCurrency($key, $value, $this->currencyCode($attributes));
        }

        throw new InvalidArgumentException('Money attributes must be instances of '.Money::class.' or integer minor amounts.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function currencyCode(array $attributes): string
    {
        if ($this->currencyAttribute !== null && filled($attributes[$this->currencyAttribute] ?? null)) {
            return (string) $attributes[$this->currencyAttribute];
        }

        return $this->currencyCode;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function withCurrency(string $key, int|string|null $amount, ?string $currencyCode): array
    {
        $values = [$key => $amount];

        if ($this->currencyAttribute !== null) {
            $values[$this->currencyAttribute] = $currencyCode;
        }

        return $values;
    }
}
