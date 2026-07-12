<?php

use App\Enums\CalendarFeedSyncStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_feed_sources', function (Blueprint $table) {
            $table->id();
            $table->string('section')->unique();
            $table->text('feed_url');
            $table->boolean('is_enabled')->default(true);
            $table->dateTime('last_synced_at')->nullable();
            $table->string('last_sync_status')->default(CalendarFeedSyncStatus::NEVER->value);
            $table->text('last_sync_error')->nullable();
            $table->unsignedInteger('last_event_count')->nullable();
            $table->string('etag')->nullable();
            $table->string('last_modified')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_feed_sources');
    }
};
