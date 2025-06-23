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
        Schema::create('task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('daily_log_id')->constrained()->onDelete('cascade');
            $table->date('completion_date');
            $table->boolean('is_completed')->default(false);
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
            $table->integer('actual_duration')->nullable()->comment('Actual duration in minutes');
            $table->integer('quality_score')->nullable()->comment('Quality rating 1-10');
            $table->integer('difficulty_level')->nullable()->comment('Difficulty level 1-10');
            $table->integer('energy_before')->nullable()->comment('Energy before task 1-10');
            $table->integer('energy_after')->nullable()->comment('Energy after task 1-10');
            $table->enum('completion_status', ['not_started', 'in_progress', 'completed', 'skipped', 'postponed'])->default('not_started');
            $table->text('notes')->nullable();
            $table->text('obstacles')->nullable();
            $table->text('improvements')->nullable();
            $table->json('tags')->nullable()->comment('Array of completion tags');
            $table->timestamps();

            $table->unique(['routine_task_id', 'completion_date']);
            $table->index(['completion_date', 'is_completed']);
            $table->index(['daily_log_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_completions');
    }
};