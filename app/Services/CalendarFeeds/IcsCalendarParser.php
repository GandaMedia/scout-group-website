<?php

namespace App\Services\CalendarFeeds;

use App\Enums\Section;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class IcsCalendarParser
{
    /**
     * @return Collection<int, ParsedCalendarFeedEvent>
     */
    public function parse(string $contents, Section $section, int $feedSourceId): Collection
    {
        $lines = $this->unfoldLines($contents);

        if (! collect($lines)->contains('BEGIN:VCALENDAR')) {
            throw new InvalidArgumentException('The feed did not return a valid iCalendar payload.');
        }

        $events = collect();
        $currentEvent = null;

        foreach ($lines as $line) {
            if ($line === 'BEGIN:VEVENT') {
                $currentEvent = [];

                continue;
            }

            if ($line === 'END:VEVENT') {
                if ($currentEvent !== null) {
                    $events->push($this->buildParsedEvent($currentEvent, $section, $feedSourceId));
                }

                $currentEvent = null;

                continue;
            }

            if ($currentEvent === null) {
                continue;
            }

            [$name, $parameters, $value] = $this->parseProperty($line);
            $currentEvent[$name] = [
                'parameters' => $parameters,
                'value' => $value,
            ];
        }

        return $events;
    }

    /**
     * @return list<string>
     */
    private function unfoldLines(string $contents): array
    {
        $contents = str_replace(["\r\n", "\r"], "\n", trim($contents));
        $lines = explode("\n", $contents);
        $unfolded = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if (($line[0] === ' ' || $line[0] === "\t") && $unfolded !== []) {
                $unfolded[array_key_last($unfolded)] .= substr($line, 1);

                continue;
            }

            $unfolded[] = $line;
        }

        return $unfolded;
    }

    /**
     * @param  array<string, array{parameters: array<string, string>, value: string}>  $properties
     */
    private function buildParsedEvent(array $properties, Section $section, int $feedSourceId): ParsedCalendarFeedEvent
    {
        $title = $this->decodeValue($properties['SUMMARY']['value'] ?? 'Untitled event');
        $content = isset($properties['DESCRIPTION'])
            ? $this->decodeValue($properties['DESCRIPTION']['value'])
            : null;

        $startsAtProperty = $properties['DTSTART'] ?? null;

        if ($startsAtProperty === null) {
            throw new InvalidArgumentException('The feed contained an event without a DTSTART value.');
        }

        $endsAtProperty = $properties['DTEND'] ?? null;
        $startsAt = $this->parseDate($startsAtProperty['value'], $startsAtProperty['parameters']);
        $allDay = ($startsAtProperty['parameters']['VALUE'] ?? null) === 'DATE';
        $endsAt = $endsAtProperty !== null
            ? $this->parseDate($endsAtProperty['value'], $endsAtProperty['parameters'])
            : $startsAt;

        if ($allDay) {
            $endsAt = $endsAtProperty !== null
                ? $endsAt->subDay()
                : $startsAt;
        }

        if ($endsAt->lt($startsAt)) {
            $endsAt = $startsAt;
        }

        $normalizedTitle = Str::squish(Str::lower($title));
        $sourceFingerprint = hash('sha256', implode('|', [
            $normalizedTitle,
            $startsAt->toIso8601String(),
            $endsAt->toIso8601String(),
            $allDay ? '1' : '0',
        ]));

        $payloadHash = hash('sha256', implode('|', [
            $normalizedTitle,
            $startsAt->toIso8601String(),
            $endsAt->toIso8601String(),
            $allDay ? '1' : '0',
            $content ?? '',
        ]));

        $externalEventUid = isset($properties['UID'])
            ? trim($properties['UID']['value'])
            : null;

        $externalEventKey = hash('sha256', $externalEventUid ?: $sourceFingerprint);

        return new ParsedCalendarFeedEvent(
            feedSourceId: $feedSourceId,
            section: $section,
            title: $title,
            startsAt: $startsAt,
            endsAt: $endsAt,
            allDay: $allDay,
            content: $content,
            externalEventKey: $externalEventKey,
            externalEventUid: $externalEventUid,
            mergeKey: $sourceFingerprint,
            sourceFingerprint: $sourceFingerprint,
            payloadHash: $payloadHash,
        );
    }

    /**
     * @return array{0: string, 1: array<string, string>, 2: string}
     */
    private function parseProperty(string $line): array
    {
        [$rawName, $value] = explode(':', $line, 2);
        $segments = explode(';', $rawName);
        $name = strtoupper(array_shift($segments));
        $parameters = [];

        foreach ($segments as $segment) {
            [$parameterName, $parameterValue] = array_pad(explode('=', $segment, 2), 2, '');
            $parameters[strtoupper($parameterName)] = $parameterValue;
        }

        return [$name, $parameters, $value];
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function parseDate(string $value, array $parameters): CarbonImmutable
    {
        $timezone = $parameters['TZID'] ?? config('app.timezone');

        if (($parameters['VALUE'] ?? null) === 'DATE') {
            return CarbonImmutable::createFromFormat('Ymd', $value, config('app.timezone'))->startOfDay();
        }

        if (str_ends_with($value, 'Z')) {
            return CarbonImmutable::createFromFormat('Ymd\THis\Z', $value, 'UTC')
                ->setTimezone(config('app.timezone'));
        }

        return CarbonImmutable::createFromFormat('Ymd\THis', $value, $timezone)
            ->setTimezone(config('app.timezone'));
    }

    private function decodeValue(string $value): string
    {
        return str_replace(
            ['\\n', '\\N', '\\,', '\\;', '\\\\'],
            ["\n", "\n", ',', ';', '\\'],
            trim($value),
        );
    }
}
