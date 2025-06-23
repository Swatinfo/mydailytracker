<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'description',
        'category',
        'total_pages',
        'current_page',
        'status',
        'started_date',
        'completed_date',
        'priority',
        'rating',
        'review',
        'key_insights',
        'action_items',
        'cover_image_url',
        'purchase_url',
        'price',
        'format',
        'notes',
        'tags',
    ];

    protected $casts = [
        'started_date' => 'date',
        'completed_date' => 'date',
        'total_pages' => 'integer',
        'current_page' => 'integer',
        'priority' => 'integer',
        'rating' => 'integer',
        'key_insights' => 'array',
        'action_items' => 'array',
        'tags' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * Get the reading sessions for the book.
     */
    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    /**
     * Get recent reading sessions.
     */
    public function recentReadingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class)
            ->orderBy('session_date', 'desc')
            ->limit(10);
    }

    /**
     * Calculate reading progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_pages <= 0) {
            return 0;
        }

        return round(($this->current_page / $this->total_pages) * 100, 1);
    }

    /**
     * Get pages remaining.
     */
    public function getPagesRemainingAttribute(): int
    {
        return max(0, $this->total_pages - $this->current_page);
    }

    /**
     * Calculate estimated reading time based on average reading speed.
     */
    public function getEstimatedTimeRemainingAttribute(): float
    {
        $averageSpeed = $this->getAverageReadingSpeed();
        if ($averageSpeed <= 0) {
            // Default reading speed: 2 pages per minute
            $averageSpeed = 2;
        }

        return round($this->pages_remaining / $averageSpeed, 1);
    }

    /**
     * Get total reading time for this book.
     */
    public function getTotalReadingTimeAttribute(): int
    {
        return $this->readingSessions()->sum('duration_minutes');
    }

    /**
     * Get total pages read in sessions.
     */
    public function getTotalPagesReadAttribute(): int
    {
        return $this->readingSessions()->sum('pages_read');
    }

    /**
     * Calculate average reading speed (pages per minute).
     */
    public function getAverageReadingSpeed(): float
    {
        $totalTime = $this->total_reading_time;
        $totalPages = $this->total_pages_read;

        if ($totalTime <= 0 || $totalPages <= 0) {
            return 0;
        }

        return round($totalPages / $totalTime, 2);
    }

    /**
     * Get reading streak (consecutive days).
     */
    public function getCurrentReadingStreak(): int
    {
        $sessions = $this->readingSessions()
            ->orderBy('session_date', 'desc')
            ->get()
            ->groupBy('session_date');

        $streak = 0;
        $checkDate = today();

        foreach ($sessions as $date => $daySessions) {
            if (Carbon::parse($date)->eq($checkDate)) {
                $streak++;
                $checkDate = $checkDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get average session duration.
     */
    public function getAverageSessionDurationAttribute(): float
    {
        $avgDuration = $this->readingSessions()->avg('duration_minutes');
        return $avgDuration ? round($avgDuration, 1) : 0;
    }

    /**
     * Get days since started reading.
     */
    public function getDaysSinceStartedAttribute(): int
    {
        if (!$this->started_date) {
            return 0;
        }

        return $this->started_date->diffInDays(today());
    }

    /**
     * Get estimated completion date based on current reading pace.
     */
    public function getEstimatedCompletionDateAttribute(): ?Carbon
    {
        if ($this->status === 'completed' || $this->pages_remaining <= 0) {
            return null;
        }

        $recentSessions = $this->readingSessions()
            ->where('session_date', '>=', now()->subDays(14))
            ->get();

        if ($recentSessions->isEmpty()) {
            return null;
        }

        $avgPagesPerDay = $recentSessions->sum('pages_read') / 14;

        if ($avgPagesPerDay <= 0) {
            return null;
        }

        $daysToComplete = ceil($this->pages_remaining / $avgPagesPerDay);
        return today()->addDays($daysToComplete);
    }

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'business' => 'Business',
            'technical' => 'Technical',
            'personal_development' => 'Personal Development',
            'leadership' => 'Leadership',
            'strategy' => 'Strategy',
            'biography' => 'Biography',
            'fiction' => 'Fiction',
            'other' => 'Other',
            default => 'Business'
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'want_to_read' => 'Want to Read',
            'currently_reading' => 'Currently Reading',
            'completed' => 'Completed',
            'paused' => 'Paused',
            'abandoned' => 'Abandoned',
            default => 'Want to Read'
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'want_to_read' => '#6b7280',     // gray
            'currently_reading' => '#3b82f6', // blue
            'completed' => '#10b981',        // green
            'paused' => '#f59e0b',          // yellow
            'abandoned' => '#ef4444',       // red
            default => '#6b7280'
        };
    }

    /**
     * Get priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            1 => 'Very Low',
            2 => 'Low',
            3 => 'Medium',
            4 => 'High',
            5 => 'Very High',
            default => 'Medium'
        };
    }

    /**
     * Start reading the book.
     */
    public function startReading()
    {
        $this->update([
            'status' => 'currently_reading',
            'started_date' => today(),
        ]);
    }

    /**
     * Mark book as completed.
     */
    public function markAsCompleted($rating = null, $review = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => today(),
            'current_page' => $this->total_pages,
            'rating' => $rating,
            'review' => $review,
        ]);
    }

    /**
     * Update reading progress.
     */
    public function updateProgress($currentPage)
    {
        $this->update(['current_page' => min($currentPage, $this->total_pages)]);

        if ($this->current_page >= $this->total_pages && $this->status !== 'completed') {
            $this->markAsCompleted();
        }
    }

    /**
     * Scope to get currently reading books.
     */
    public function scopeCurrentlyReading($query)
    {
        return $query->where('status', 'currently_reading');
    }

    /**
     * Scope to get completed books.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get books by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get books by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get high priority books.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 4);
    }

    /**
     * Scope to get books ordered by priority.
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope to get recently started books.
     */
    public function scopeRecentlyStarted($query, $days = 30)
    {
        return $query->where('started_date', '>=', now()->subDays($days));
    }

    /**
     * Scope to get recently completed books.
     */
    public function scopeRecentlyCompleted($query, $days = 30)
    {
        return $query->where('completed_date', '>=', now()->subDays($days));
    }
}