<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_list_entries', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('section_slug');
            $table->string('parent_name');
            $table->string('parent_email');
            $table->string('parent_phone');
            $table->string('postcode');
            $table->text('notes');
            $table->boolean('is_possible_duplicate')->default(false)->index();
            $table->string('duplicate_reason')->nullable();
            $table->timestamp('duplicate_detected_at')->nullable();
            $table->string('sync_status')->default('pending')->index();
            $table->unsignedInteger('sync_attempts')->default(0);
            $table->timestamp('submitted_at');
            $table->timestamp('sync_queued_at')->nullable();
            $table->timestamp('sync_attempted_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->unsignedBigInteger('osm_scout_id')->nullable();
            $table->json('last_payload')->nullable();
            $table->json('osm_response')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamps();

            $table->index(['section_slug', 'date_of_birth']);
            $table->index(['first_name', 'last_name', 'date_of_birth', 'section_slug'], 'waiting_list_entries_duplicate_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_list_entries');
    }
};
