<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leader_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_id')->constrained()->cascadeOnDelete();
            $table->string('section');
            $table->unsignedInteger('order_column')->nullable();
            $table->timestamps();

            $table->unique(['leader_id', 'section']);
            $table->index(['section', 'order_column']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leader_sections');
    }
};
