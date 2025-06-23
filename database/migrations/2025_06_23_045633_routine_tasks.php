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
        Schema::create('routine_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('estimated_duration')->comment('Duration in minutes');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->json('days_of_week')->comment('Array of day numbers: 0=Sunday, 1=Monday, etc.');
            $table->boolean('is_flexible')->default(false)->comment('Can be moved to different time');
            $table->integer('target_quality_score')->default(8)->comment('Target quality score 1-10');
            $table->text('success_criteria')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'start_time']);
            $table->index(['routine_category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_tasks');
    }
};