<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_feed_event_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('calendar_feed_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_event_key');
            $table->string('external_event_uid')->nullable();
            $table->string('merge_key');
            $table->string('source_fingerprint');
            $table->string('payload_hash');
            $table->dateTime('last_seen_at');
            $table->timestamps();

            $table->unique(['calendar_feed_source_id', 'external_event_key'], 'calendar_feed_event_links_source_event_unique');
            $table->index('merge_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_feed_event_links');
    }
};
