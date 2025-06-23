<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class RoutineTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'routine_category_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'estimated_duration',
        'priority',
        'days_of_week',
        'is_flexible',
        'target_quality_score',
        'success_criteria',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'days_of_week' => 'array',
        'is_flexible' => 'boolean',
        'is_active' => 'boolean',
        'estimated_duration' => 'integer',
        'target_quality_score' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category that owns the routine task.
     */
    public function routineCategory(): BelongsTo
    {
        return $this->belongsTo(RoutineCategory::class);
    }

    /**
     * Get the task completions for the routine task.
     */
    public function taskCompletions(): HasMany
    {
        return $this->hasMany(TaskCompletion::class);
    }

    /**
     * Get today's task completion.
     */
    public function todaysCompletion()
    {
        return $this->taskCompletions()
            ->where('completion_date', today())
            ->first();
    }

    /**
     * Get task completion for a specific date.
     */
    public function getCompletionForDate($date)
    {
        return $this->taskCompletions()
            ->where('completion_date', $date)
            ->first();
    }

    /**
     * Check if task is scheduled for today.
     */
    public function isScheduledForToday(): bool
    {
        $today = now()->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
        return in_array($today, $this->days_of_week ?? []);
    }

    /**
     * Check if task is scheduled for a specific date.
     */
    public function isScheduledForDate($date): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        return in_array($dayOfWeek, $this->days_of_week ?? []);
    }

    /**
     * Check if task is completed today.
     */
    public function isCompletedToday(): bool
    {
        return $this->taskCompletions()
            ->where('completion_date', today())
            ->where('is_completed', true)
            ->exists();
    }

    /**
     * Check if task is completed for a specific date.
     */
    public function isCompletedForDate($date): bool
    {
        return $this->taskCompletions()
            ->where('completion_date', $date)
            ->where('is_completed', true)
            ->exists();
    }

    /**
     * Get completion rate for a date range.
     */
    public function getCompletionRate($startDate, $endDate)
    {
        $period = Carbon::parse($startDate)->daysUntil($endDate);
        $totalScheduledDays = 0;
        $completedDays = 0;

        foreach ($period as $date) {
            if ($this->isScheduledForDate($date)) {
                $totalScheduledDays++;
                if ($this->isCompletedForDate($date)) {
                    $completedDays++;
                }
            }
        }

        return $totalScheduledDays > 0 ? round(($completedDays / $totalScheduledDays) * 100, 1) : 0;
    }

    /**
     * Get average quality score for a date range.
     */
    public function getAverageQualityScore($startDate, $endDate)
    {
        $avgQuality = $this->taskCompletions()
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->where('is_completed', true)
            ->whereNotNull('quality_score')
            ->avg('quality_score');

        return $avgQuality ? round($avgQuality, 1) : 0;
    }

    /**
     * Get total time spent on this task in a date range.
     */
    public function getTotalTimeSpent($startDate, $endDate)
    {
        return $this->taskCompletions()
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->where('is_completed', true)
            ->whereNotNull('actual_duration')
            ->sum('actual_duration');
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    /**
     * Get priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
            default => 'Medium'
        };
    }

    /**
     * Get priority color.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => '#10b981',      // green
            'medium' => '#f59e0b',   // yellow
            'high' => '#f97316',     // orange
            'critical' => '#ef4444', // red
            default => '#f59e0b'
        };
    }

    /**
     * Get days of week as formatted string.
     */
    public function getDaysOfWeekStringAttribute(): string
    {
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $days = collect($this->days_of_week)->map(fn($day) => $dayNames[$day]);
        return $days->join(', ');
    }

    /**
     * Scope to get active tasks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get tasks ordered by time.
     */
    public function scopeOrderedByTime($query)
    {
        return $query->orderBy('start_time');
    }

    /**
     * Scope to get tasks for today.
     */
    public function scopeForToday($query)
    {
        $today = now()->dayOfWeek;
        return $query->where('is_active', true)
            ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $today . '"']);
    }

    /**
     * Scope to get tasks for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        return $query->where('is_active', true)
            ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $dayOfWeek . '"']);
    }

    /**
     * Scope to get tasks by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}