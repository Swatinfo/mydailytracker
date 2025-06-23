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
        Schema::create('weekly_reviews', function (Blueprint $table) {
            $table->id();
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->integer('year');
            $table->integer('week_number');
            $table->decimal('overall_completion_rate', 5, 2)->nullable()->comment('Overall completion percentage');
            $table->decimal('average_quality_score', 3, 1)->nullable()->comment('Average quality score for the week');
            $table->decimal('average_energy_level', 3, 1)->nullable()->comment('Average energy level for the week');
            $table->integer('total_reading_minutes')->default(0);
            $table->integer('books_completed')->default(0);
            $table->integer('total_pages_read')->default(0);
            $table->json('category_performance')->nullable()->comment('Performance by category');
            $table->json('daily_completion_rates')->nullable()->comment('Completion rates for each day');
            $table->text('week_highlights')->nullable();
            $table->text('challenges_faced')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->text('next_week_focus')->nullable();
            $table->json('goals_achieved')->nullable()->comment('Array of goals achieved this week');
            $table->json('goals_missed')->nullable()->comment('Array of goals missed this week');
            $table->json('habits_analysis')->nullable()->comment('Analysis of habit consistency');
            $table->integer('stress_level_avg')->nullable()->comment('Average stress level 1-10');
            $table->integer('satisfaction_avg')->nullable()->comment('Average satisfaction 1-10');
            $table->boolean('exercise_consistency')->default(false)->comment('Met exercise goals');
            $table->boolean('sleep_consistency')->default(false)->comment('Met sleep goals');
            $table->boolean('reading_consistency')->default(false)->comment('Met reading goals');
            $table->text('improvement_areas')->nullable();
            $table->text('celebration_notes')->nullable();
            $table->json('metrics')->nullable()->comment('Additional weekly metrics');
            $table->timestamps();

            $table->unique(['year', 'week_number']);
            $table->index(['week_start_date', 'week_end_date']);
            $table->index(['year', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_reviews');
    }
};