<?php

namespace App\Jobs;

use App\Http\Integrations\Osm\OsmCdnConnector;
use App\Http\Integrations\Osm\Requests\GetMemberPhotoRequest;
use App\Models\Leader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportOsmLeaderPhoto implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $leaderId,
        public string $scoutId,
        public string $photoGuid,
    ) {}

    public function handle(): void
    {
        $leader = Leader::query()
            ->with('media')
            ->find($this->leaderId);

        if (! $leader instanceof Leader || $leader->hasMedia('photo')) {
            return;
        }

        $response = (new OsmCdnConnector)
            ->send(new GetMemberPhotoRequest($this->scoutId, $this->photoGuid));

        $contentType = (string) $response->header('Content-Type');

        if ($response->failed() || ! str_starts_with($contentType, 'image/')) {
            Log::warning('OSM leader photo import did not return an image.', [
                'leader_id' => $this->leaderId,
                'scout_id' => $this->scoutId,
                'status' => $response->status(),
                'content_type' => $contentType,
            ]);

            return;
        }

        $leader
            ->addMediaFromString($response->body())
            ->usingName($leader->name.' OSM photo')
            ->usingFileName('osm-photo-'.$this->scoutId.'.'.$this->extensionForContentType($contentType))
            ->withCustomProperties([
                'source' => 'osm',
                'osm_scout_id' => $this->scoutId,
                'osm_photo_guid' => $this->photoGuid,
            ])
            ->toMediaCollection('photo');
    }

    public function failed(Throwable $throwable): void
    {
        Log::warning('OSM leader photo import job failed.', [
            'leader_id' => $this->leaderId,
            'scout_id' => $this->scoutId,
            'message' => $throwable->getMessage(),
        ]);
    }

    private function extensionForContentType(string $contentType): string
    {
        return match (str($contentType)->before(';')->lower()->value()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}
