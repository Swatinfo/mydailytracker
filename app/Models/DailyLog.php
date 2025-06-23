<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class DailyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_date',
        'morning_energy_level',
        'evening_energy_level',
        'overall_satisfaction',
        'stress_level',
        'focus_quality',
        'daily_reflection',
        'tomorrow_priorities',
        'lessons_learned',
        'gratitude_notes',
        'mood_tags',
        'sleep_time',
        'wake_time',
        'sleep_quality',
        'exercise_completed',
        'exercise_duration',
        'notes',
    ];

    protected $casts = [
        'log_date' => 'date',
        'mood_tags' => 'array',
        'sleep_time' => 'datetime:H:i',
        'wake_time' => 'datetime:H:i',
        'exercise_completed' => 'boolean',
        'morning_energy_level' => 'integer',
        'evening_energy_level' => 'integer',
        'overall_satisfaction' => 'integer',
        'stress_level' => 'integer',
        'focus_quality' => 'integer',
        'sleep_quality' => 'integer',
        'exercise_duration' => 'integer',
    ];

    /**
     * Get the task completions for the daily log.
     */
    public function taskCompletions(): HasMany
    {
        return $this->hasMany(TaskCompletion::class);
    }

    /**
     * Get the reading sessions for the daily log.
     */
    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    /**
     * Get today's daily log or create a new one.
     */
    public static function getOrCreateToday()
    {
        return static::firstOrCreate(
            ['log_date' => today()],
            [
                'morning_energy_level' => null,
                'evening_energy_level' => null,
                'overall_satisfaction' => null,
                'stress_level' => null,
                'focus_quality' => null,
            ]
        );
    }

    /**
     * Get daily log for a specific date or create one.
     */
    public static function getOrCreateForDate($date)
    {
        return static::firstOrCreate(
            ['log_date' => $date],
            [
                'morning_energy_level' => null,
                'evening_energy_level' => null,
                'overall_satisfaction' => null,
                'stress_level' => null,
                'focus_quality' => null,
            ]
        );
    }

    /**
     * Calculate overall completion rate for the day.
     */
    public function getCompletionRateAttribute()
    {
        $totalTasks = $this->taskCompletions()->count();
        $completedTasks = $this->taskCompletions()->where('is_completed', true)->count();

        return $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
    }

    /**
     * Calculate average quality score for the day.
     */
    public function getAverageQualityScoreAttribute()
    {
        $avgQuality = $this->taskCompletions()
            ->where('is_completed', true)
            ->whereNotNull('quality_score')
            ->avg('quality_score');

        return $avgQuality ? round($avgQuality, 1) : 0;
    }

    /**
     * Get total reading time for the day.
     */
    public function getTotalReadingTimeAttribute()
    {
        return $this->readingSessions()->sum('duration_minutes');
    }

    /**
     * Get total pages read for the day.
     */
    public function getTotalPagesReadAttribute()
    {
        return $this->readingSessions()->sum('pages_read');
    }

    /**
     * Calculate sleep duration in hours.
     */
    public function getSleepDurationAttribute()
    {
        if (!$this->sleep_time || !$this->wake_time) {
            return null;
        }

        $sleepTime = Carbon::parse($this->sleep_time);
        $wakeTime = Carbon::parse($this->wake_time);

        // If wake time is before sleep time, add a day
        if ($wakeTime->lt($sleepTime)) {
            $wakeTime->addDay();
        }

        return round($sleepTime->diffInHours($wakeTime, true), 1);
    }

    /**
     * Get energy level change (evening - morning).
     */
    public function getEnergyChangeAttribute()
    {
        if (!$this->morning_energy_level || !$this->evening_energy_level) {
            return null;
        }

        return $this->evening_energy_level - $this->morning_energy_level;
    }

    /**
     * Check if the day meets CEO excellence targets.
     */
    public function meetsExcellenceTargets()
    {
        return [
            'completion_rate' => $this->completion_rate >= 85,
            'quality_score' => $this->average_quality_score >= 8,
            'reading_time' => $this->total_reading_time >= 30,
            'exercise' => $this->exercise_completed,
            'energy_maintained' => $this->energy_change >= -1, // Energy didn't drop by more than 1 point
            'satisfaction' => $this->overall_satisfaction >= 8,
        ];
    }

    /**
     * Get targets met count.
     */
    public function getTargetsMetCountAttribute()
    {
        return collect($this->meetsExcellenceTargets())->filter()->count();
    }

    /**
     * Get performance summary.
     */
    public function getPerformanceSummary()
    {
        return [
            'completion_rate' => $this->completion_rate,
            'quality_score' => $this->average_quality_score,
            'reading_time' => $this->total_reading_time,
            'tasks_completed' => $this->taskCompletions()->where('is_completed', true)->count(),
            'total_tasks' => $this->taskCompletions()->count(),
            'exercise_completed' => $this->exercise_completed,
            'targets_met' => $this->targets_met_count,
            'excellence_score' => round(($this->targets_met_count / 6) * 100, 1),
        ];
    }

    /**
     * Scope to get logs for date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('log_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get logs for current week.
     */
    public function scopeThisWeek($query)
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        return $query->whereBetween('log_date', [$startOfWeek, $endOfWeek]);
    }

    /**
     * Scope to get logs for current month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('log_date', now()->month)
            ->whereYear('log_date', now()->year);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('log_date', '>=', now()->subDays($days));
    }
}