<?php

namespace App\Models;

use Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoutineCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the routine tasks for this category.
     */
    public function routineTasks(): HasMany
    {
        return $this->hasMany(RoutineTask::class);
    }

    /**
     * Get active routine tasks for this category.
     */
    public function activeRoutineTasks(): HasMany
    {
        return $this->hasMany(RoutineTask::class)->where('is_active', true);
    }

    /**
     * Get routine tasks ordered by time for this category.
     */
    public function routineTasksOrdered(): HasMany
    {
        return $this->hasMany(RoutineTask::class)
            ->where('is_active', true)
            ->orderBy('start_time');
    }

    /**
     * Get today's tasks for this category.
     */
    public function todaysTasks()
    {
        $today = now()->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.

        return $this->routineTasks()
            ->where('is_active', true)
            ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $today . '"'])
            ->orderBy('start_time');
    }

    /**
     * Get tasks for a specific date.
     */
    public function tasksForDate($date)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        return $this->routineTasks()
            ->where('is_active', true)
            ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $dayOfWeek . '"'])
            ->orderBy('start_time');
    }

    /**
     * Calculate completion rate for a specific date.
     */
    public function getCompletionRateForDate($date)
    {
        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;

        $tasks = $this->routineTasks()
            ->where('is_active', true)
            ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $dayOfWeek . '"'])
            ->get();

        if ($tasks->isEmpty()) {
            return 0;
        }

        $completedTasks = $tasks->filter(function ($task) use ($date) {
            return $task->taskCompletions()
                ->where('completion_date', $date)
                ->where('is_completed', true)
                ->exists();
        });

        return round(($completedTasks->count() / $tasks->count()) * 100, 1);
    }

    /**
     * Get average quality score for a date range.
     */
    public function getAverageQualityScore($startDate, $endDate)
    {
        $taskIds = $this->routineTasks()->pluck('id');

        $avgQuality = TaskCompletion::whereIn('routine_task_id', $taskIds)
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->where('is_completed', true)
            ->whereNotNull('quality_score')
            ->avg('quality_score');

        return $avgQuality ? round($avgQuality, 1) : 0;
    }

    /**
     * Scope to get active categories ordered by sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope to get categories ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}