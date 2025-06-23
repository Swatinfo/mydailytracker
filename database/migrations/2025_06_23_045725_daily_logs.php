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
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date');
            $table->integer('morning_energy_level')->nullable()->comment('Energy level 1-10');
            $table->integer('evening_energy_level')->nullable()->comment('Energy level 1-10');
            $table->integer('overall_satisfaction')->nullable()->comment('Day satisfaction 1-10');
            $table->integer('stress_level')->nullable()->comment('Stress level 1-10');
            $table->integer('focus_quality')->nullable()->comment('Focus quality 1-10');
            $table->text('daily_reflection')->nullable();
            $table->text('tomorrow_priorities')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->text('gratitude_notes')->nullable();
            $table->json('mood_tags')->nullable()->comment('Array of mood descriptors');
            $table->time('sleep_time')->nullable();
            $table->time('wake_time')->nullable();
            $table->integer('sleep_quality')->nullable()->comment('Sleep quality 1-10');
            $table->boolean('exercise_completed')->default(false);
            $table->integer('exercise_duration')->nullable()->comment('Exercise duration in minutes');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('log_date');
            $table->index('log_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};