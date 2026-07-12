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
        Schema::dropIfExists('meal_shopping_item');
        Schema::dropIfExists('shopping_items');
        Schema::dropIfExists('meal_food_items');
        Schema::dropIfExists('food_prices');
        Schema::dropIfExists('food_items');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('project_cost_snapshots');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('stores');

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('people_count');
            $table->date('event_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'event_date']);
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('food_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('search_brand')->nullable();
            $table->string('search_store')->nullable();
            $table->unsignedInteger('servings_per_pack');
            $table->unsignedInteger('calories_per_pack');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'store_id']);
        });

        Schema::create('food_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('food_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('price_per_pack');
            $table->date('priced_at');
            $table->timestamps();

            $table->index(['food_item_id', 'priced_at']);
        });

        Schema::create('meals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('meal_type');
            $table->unsignedInteger('day_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'day_number', 'meal_type']);
        });

        Schema::create('meal_food_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('food_price_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount_per_serving', 8, 2);
            $table->unsignedInteger('price_per_pack');
            $table->unsignedInteger('servings_per_pack');
            $table->unsignedInteger('calories_per_pack');
            $table->date('priced_at');
            $table->timestamps();

            $table->index(['meal_id', 'food_item_id']);
        });

        Schema::create('project_cost_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_cost');
            $table->unsignedInteger('cost_per_head');
            $table->unsignedInteger('total_calories_per_serving');
            $table->unsignedInteger('meal_count');
            $table->string('snapshot_reason');
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_cost_snapshots');
        Schema::dropIfExists('meal_food_items');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('food_prices');
        Schema::dropIfExists('food_items');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('projects');

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->foreignId('user_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('qty');
            $table->foreignId('project_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shopping_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('store');
            $table->integer('servings_per_pack');
            $table->integer('calories_per_pack');
            $table->integer('cost_per_pack');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meal_shopping_item', function (Blueprint $table): void {
            $table->foreignId('meal_id');
            $table->foreignId('shopping_item_id');
        });
    }
};
