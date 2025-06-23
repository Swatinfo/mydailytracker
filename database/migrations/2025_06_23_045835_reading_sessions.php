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
        Schema::create('reading_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('daily_log_id')->nullable()->constrained()->onDelete('set null');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->comment('Session duration in minutes');
            $table->integer('pages_read')->default(0);
            $table->integer('start_page');
            $table->integer('end_page');
            $table->enum('session_type', ['scheduled', 'bonus', 'catchup', 'review'])->default('scheduled');
            $table->enum('location', ['office', 'home', 'commute', 'cafe', 'other'])->default('office');
            $table->integer('focus_level')->nullable()->comment('Focus level 1-10');
            $table->integer('comprehension_level')->nullable()->comment('Comprehension level 1-10');
            $table->integer('enjoyment_level')->nullable()->comment('Enjoyment level 1-10');
            $table->text('session_notes')->nullable();
            $table->text('key_insights')->nullable();
            $table->text('quotes')->nullable();
            $table->json('action_items')->nullable()->comment('Array of action items from this session');
            $table->json('questions')->nullable()->comment('Array of questions that arose');
            $table->boolean('took_notes')->default(false);
            $table->boolean('discussed_with_others')->default(false);
            $table->enum('mood_before', ['excited', 'focused', 'tired', 'distracted', 'neutral'])->nullable();
            $table->enum('mood_after', ['energized', 'satisfied', 'confused', 'inspired', 'neutral'])->nullable();
            $table->timestamps();

            $table->index(['session_date', 'book_id']);
            $table->index(['book_id', 'session_date']);
            $table->index('session_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_sessions');
    }
};