<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->boolean('is_manual')->default(true)->after('all_day');
            $table->string('sync_merge_key')->nullable()->after('is_manual');

            $table->index('is_manual');
            $table->index('sync_merge_key');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropIndex(['is_manual']);
            $table->dropIndex(['sync_merge_key']);
            $table->dropColumn([
                'is_manual',
                'sync_merge_key',
            ]);
        });
    }
};
