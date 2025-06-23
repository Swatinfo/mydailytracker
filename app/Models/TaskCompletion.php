<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TaskCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'routine_task_id',
        'daily_log_id',
        'completion_date',
        'is_completed',
        'actual_start_time',
        'actual_end_time',
        'actual_duration',
        'quality_score',
        'difficulty_level',
        'energy_before',
        'energy_after',
        'completion_status',
        'notes',
        'obstacles',
        'improvements',
        'tags',
    ];

    protected $casts = [
        'completion_date' => 'date',
        'is_completed' => 'boolean',
        'actual_start_time' => 'datetime:H:i',
        'actual_end_time' => 'datetime:H:i',
        'actual_duration' => 'integer',
        'quality_score' => 'integer',
        'difficulty_level' => 'integer',
        'energy_before' => 'integer',
        'energy_after' => 'integer',
        'tags' => 'array',
    ];

    /**
     * Get the routine task that owns the task completion.
     */
    public function routineTask(): BelongsTo
    {
        return $this->belongsTo(RoutineTask::class);
    }

    /**
     * Get the daily log that owns the task completion.
     */
    public function dailyLog(): BelongsTo
    {
        return $this->belongsTo(DailyLog::class);
    }

    /**
     * Calculate actual duration from start and end times.
     */
    public function calculateActualDuration()
    {
        if (!$this->actual_start_time || !$this->actual_end_time) {
            return null;
        }

        $start = Carbon::parse($this->actual_start_time);
        $end = Carbon::parse($this->actual_end_time);

        return $start->diffInMinutes($end);
    }

    /**
     * Get energy change (after - before).
     */
    public function getEnergyChangeAttribute()
    {
        if (!$this->energy_before || !$this->energy_after) {
            return null;
        }

        return $this->energy_after - $this->energy_before;
    }

    /**
     * Get duration variance compared to estimated duration.
     */
    public function getDurationVarianceAttribute()
    {
        if (!$this->actual_duration || !$this->routineTask) {
            return null;
        }

        $estimated = $this->routineTask->estimated_duration;
        return $this->actual_duration - $estimated;
    }

    /**
     * Get duration variance percentage.
     */
    public function getDurationVariancePercentageAttribute()
    {
        if (!$this->actual_duration || !$this->routineTask) {
            return null;
        }

        $estimated = $this->routineTask->estimated_duration;
        if ($estimated == 0) return null;

        return round((($this->actual_duration - $estimated) / $estimated) * 100, 1);
    }

    /**
     * Check if task was completed on time.
     */
    public function isOnTime(): bool
    {
        if (!$this->actual_end_time || !$this->routineTask) {
            return false;
        }

        $scheduledEndTime = Carbon::parse($this->routineTask->end_time);
        $actualEndTime = Carbon::parse($this->actual_end_time);

        return $actualEndTime->lte($scheduledEndTime);
    }

    /**
     * Check if task was started on time.
     */
    public function isStartedOnTime(): bool
    {
        if (!$this->actual_start_time || !$this->routineTask) {
            return false;
        }

        $scheduledStartTime = Carbon::parse($this->routineTask->start_time);
        $actualStartTime = Carbon::parse($this->actual_start_time);

        // Allow 5 minutes grace period
        return $actualStartTime->lte($scheduledStartTime->addMinutes(5));
    }

    /**
     * Get completion status label.
     */
    public function getCompletionStatusLabelAttribute(): string
    {
        return match ($this->completion_status) {
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'skipped' => 'Skipped',
            'postponed' => 'Postponed',
            default => 'Not Started'
        };
    }

    /**
     * Get completion status color.
     */
    public function getCompletionStatusColorAttribute(): string
    {
        return match ($this->completion_status) {
            'not_started' => '#6b7280',    // gray
            'in_progress' => '#f59e0b',    // yellow
            'completed' => '#10b981',      // green
            'skipped' => '#ef4444',        // red
            'postponed' => '#f97316',      // orange
            default => '#6b7280'
        };
    }

    /**
     * Get quality performance indicator.
     */
    public function getQualityPerformanceAttribute(): string
    {
        if (!$this->quality_score) {
            return 'not_rated';
        }

        return match (true) {
            $this->quality_score >= 9 => 'excellent',
            $this->quality_score >= 8 => 'good',
            $this->quality_score >= 7 => 'satisfactory',
            $this->quality_score >= 6 => 'needs_improvement',
            default => 'poor'
        };
    }

    /**
     * Get performance summary.
     */
    public function getPerformanceSummary()
    {
        return [
            'is_completed' => $this->is_completed,
            'quality_score' => $this->quality_score,
            'on_time' => $this->isOnTime(),
            'started_on_time' => $this->isStartedOnTime(),
            'duration_variance' => $this->duration_variance,
            'energy_change' => $this->energy_change,
            'quality_performance' => $this->quality_performance,
        ];
    }

    /**
     * Mark task as completed.
     */
    public function markAsCompleted($qualityScore = null, $notes = null)
    {
        $this->update([
            'is_completed' => true,
            'completion_status' => 'completed',
            'actual_end_time' => now()->format('H:i'),
            'quality_score' => $qualityScore,
            'notes' => $notes,
        ]);

        if ($this->actual_start_time && $this->actual_end_time) {
            $this->update(['actual_duration' => $this->calculateActualDuration()]);
        }
    }

    /**
     * Start task tracking.
     */
    public function startTask($energyBefore = null)
    {
        $this->update([
            'completion_status' => 'in_progress',
            'actual_start_time' => now()->format('H:i'),
            'energy_before' => $energyBefore,
        ]);
    }

    /**
     * Scope to get completed tasks.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope to get tasks for date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('completion_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's tasks.
     */
    public function scopeToday($query)
    {
        return $query->where('completion_date', today());
    }

    /**
     * Scope to get tasks by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('completion_status', $status);
    }

    /**
     * Scope to get high quality tasks (score >= 8).
     */
    public function scopeHighQuality($query)
    {
        return $query->where('quality_score', '>=', 8);
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($completion) {
            // Auto-calculate duration if start and end times are set
            if ($completion->actual_start_time && $completion->actual_end_time && !$completion->actual_duration) {
                $completion->actual_duration = $completion->calculateActualDuration();
            }
        });
    }
}