<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ReadingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'daily_log_id',
        'session_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'pages_read',
        'start_page',
        'end_page',
        'session_type',
        'location',
        'focus_level',
        'comprehension_level',
        'enjoyment_level',
        'session_notes',
        'key_insights',
        'quotes',
        'action_items',
        'questions',
        'took_notes',
        'discussed_with_others',
        'mood_before',
        'mood_after',
    ];

    protected $casts = [
        'session_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'duration_minutes' => 'integer',
        'pages_read' => 'integer',
        'start_page' => 'integer',
        'end_page' => 'integer',
        'focus_level' => 'integer',
        'comprehension_level' => 'integer',
        'enjoyment_level' => 'integer',
        'action_items' => 'array',
        'questions' => 'array',
        'took_notes' => 'boolean',
        'discussed_with_others' => 'boolean',
    ];

    /**
     * Get the book that owns the reading session.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the daily log that owns the reading session.
     */
    public function dailyLog(): BelongsTo
    {
        return $this->belongsTo(DailyLog::class);
    }

    /**
     * Calculate duration from start and end times.
     */
    public function calculateDuration()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end);
    }

    /**
     * Calculate reading speed (pages per minute).
     */
    public function getReadingSpeedAttribute(): float
    {
        if ($this->duration_minutes <= 0 || $this->pages_read <= 0) {
            return 0;
        }

        return round($this->pages_read / $this->duration_minutes, 2);
    }

    /**
     * Get session type label.
     */
    public function getSessionTypeLabelAttribute(): string
    {
        return match ($this->session_type) {
            'scheduled' => 'Scheduled',
            'bonus' => 'Bonus',
            'catchup' => 'Catch-up',
            'review' => 'Review',
            default => 'Scheduled'
        };
    }

    /**
     * Get location label.
     */
    public function getLocationLabelAttribute(): string
    {
        return match ($this->location) {
            'office' => 'Office',
            'home' => 'Home',
            'commute' => 'Commute',
            'cafe' => 'Cafe',
            'other' => 'Other',
            default => 'Office'
        };
    }

    /**
     * Get mood before label.
     */
    public function getMoodBeforeLabelAttribute(): string
    {
        return match ($this->mood_before) {
            'excited' => 'Excited',
            'focused' => 'Focused',
            'tired' => 'Tired',
            'distracted' => 'Distracted',
            'neutral' => 'Neutral',
            default => 'Neutral'
        };
    }

    /**
     * Get mood after label.
     */
    public function getMoodAfterLabelAttribute(): string
    {
        return match ($this->mood_after) {
            'energized' => 'Energized',
            'satisfied' => 'Satisfied',
            'confused' => 'Confused',
            'inspired' => 'Inspired',
            'neutral' => 'Neutral',
            default => 'Neutral'
        };
    }

    /**
     * Check if session meets quality standards.
     */
    public function meetsQualityStandards(): bool
    {
        return $this->focus_level >= 7 &&
            $this->comprehension_level >= 7 &&
            $this->duration_minutes >= 15;
    }

    /**
     * Get quality score (average of focus, comprehension, enjoyment).
     */
    public function getQualityScoreAttribute(): float
    {
        $scores = array_filter([
            $this->focus_level,
            $this->comprehension_level,
            $this->enjoyment_level
        ]);

        if (empty($scores)) {
            return 0;
        }

        return round(array_sum($scores) / count($scores), 1);
    }

    /**
     * Check if session was productive.
     */
    public function isProductive(): bool
    {
        return $this->pages_read > 0 &&
            $this->duration_minutes >= 10 &&
            ($this->focus_level >= 6 || $this->comprehension_level >= 6);
    }

    /**
     * Get session effectiveness rating.
     */
    public function getEffectivenessRating(): string
    {
        $score = $this->quality_score;

        return match (true) {
            $score >= 9 => 'excellent',
            $score >= 8 => 'very_good',
            $score >= 7 => 'good',
            $score >= 6 => 'fair',
            $score >= 5 => 'poor',
            default => 'very_poor'
        };
    }

    /**
     * Get session summary.
     */
    public function getSessionSummary()
    {
        return [
            'pages_read' => $this->pages_read,
            'duration' => $this->duration_minutes,
            'reading_speed' => $this->reading_speed,
            'quality_score' => $this->quality_score,
            'effectiveness' => $this->getEffectivenessRating(),
            'meets_standards' => $this->meetsQualityStandards(),
            'is_productive' => $this->isProductive(),
        ];
    }

    /**
     * Update book progress after session.
     */
    public function updateBookProgress()
    {
        if ($this->book && $this->end_page > $this->book->current_page) {
            $this->book->updateProgress($this->end_page);
        }
    }

    /**
     * Create today's CEO reading session (2-3 PM).
     */
    public static function createTodaysSession($bookId, $pagesRead = 0, $notes = null)
    {
        $dailyLog = DailyLog::getOrCreateToday();

        return static::create([
            'book_id' => $bookId,
            'daily_log_id' => $dailyLog->id,
            'session_date' => today(),
            'start_time' => '14:00', // 2:00 PM
            'end_time' => '14:30',   // 2:30 PM
            'duration_minutes' => 30,
            'pages_read' => $pagesRead,
            'session_type' => 'scheduled',
            'location' => 'office',
            'session_notes' => $notes,
        ]);
    }

    /**
     * Scope to get today's sessions.
     */
    public function scopeToday($query)
    {
        return $query->where('session_date', today());
    }

    /**
     * Scope to get sessions for date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('session_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get sessions this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('session_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope to get sessions by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('session_type', $type);
    }

    /**
     * Scope to get high quality sessions.
     */
    public function scopeHighQuality($query)
    {
        return $query->where('focus_level', '>=', 8)
            ->where('comprehension_level', '>=', 8);
    }

    /**
     * Scope to get productive sessions.
     */
    public function scopeProductive($query)
    {
        return $query->where('pages_read', '>', 0)
            ->where('duration_minutes', '>=', 10);
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($session) {
            // Auto-calculate duration if start and end times are set
            if ($session->start_time && $session->end_time && !$session->duration_minutes) {
                $session->duration_minutes = $session->calculateDuration();
            }

            // Auto-calculate pages read if start and end pages are set
            if ($session->start_page && $session->end_page && !$session->pages_read) {
                $session->pages_read = $session->end_page - $session->start_page;
            }
        });

        static::saved(function ($session) {
            // Update book progress after saving
            $session->updateBookProgress();
        });
    }
}