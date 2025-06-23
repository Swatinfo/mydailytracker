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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->nullable();
            $table->text('description')->nullable();
            $table->enum('category', ['business', 'technical', 'personal_development', 'leadership', 'strategy', 'biography', 'fiction', 'other'])->default('business');
            $table->integer('total_pages');
            $table->integer('current_page')->default(0);
            $table->enum('status', ['want_to_read', 'currently_reading', 'completed', 'paused', 'abandoned'])->default('want_to_read');
            $table->date('started_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->integer('priority')->default(3)->comment('Priority 1-5, 5 being highest');
            $table->integer('rating')->nullable()->comment('Rating 1-10');
            $table->text('review')->nullable();
            $table->json('key_insights')->nullable()->comment('Array of key insights');
            $table->json('action_items')->nullable()->comment('Array of action items from the book');
            $table->string('cover_image_url')->nullable();
            $table->string('purchase_url')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->enum('format', ['physical', 'ebook', 'audiobook', 'pdf'])->default('physical');
            $table->text('notes')->nullable();
            $table->json('tags')->nullable()->comment('Array of custom tags');
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['category', 'status']);
            $table->index(['started_date', 'completed_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};