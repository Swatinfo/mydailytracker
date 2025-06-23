<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\RoutineCategory;
use App\Models\RoutineTask;
use App\Models\TaskCompletion;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RoutineController extends Controller
{
    /**
     * Display today's routine.
     */
    public function show(Request $request)
    {
        $date = $request->get('date', today());
        $date = Carbon::parse($date)->toDateString();
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Get or create daily log for the date
        $dailyLog = DailyLog::getOrCreateForDate($date);

        // Debug: Log the day calculation
        Log::info("Routine Show Debug", [
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'day_name' => Carbon::parse($date)->format('l')
        ]);




        // Get categories with their tasks for the specific date
        $categories = RoutineCategory::active()
            ->with([
                'routineTasks' => function ($query) use ($dayOfWeek) {
                    $query->where('is_active', true)
                        // ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $dayOfWeek . '"'])
                        ->whereJsonContains('days_of_week', $dayOfWeek)

                        ->orderBy('start_time');
                },
                'routineTasks.taskCompletions' => function ($query) use ($date) {
                    $query->where('completion_date', $date);
                }
            ])
            ->orderBy('sort_order')
            ->get();


        // echo "<pre>" . print_r($categories, true) . "</pre>";
        // exit;
        // Debug: Log task counts
        foreach ($categories as $category) {
            Log::info("Category Tasks", [
                'category' => $category->name,
                'task_count' => $category->routineTasks->count(),
                'tasks' => $category->routineTasks->map(function ($task) {
                    return [
                        'title' => $task->title,
                        'days_of_week' => $task->days_of_week,
                        'is_active' => $task->is_active
                    ];
                })
            ]);
        }

        // Ensure task completions exist for all scheduled tasks
        $this->ensureTaskCompletions($categories, $dailyLog, $date);

        // Reload categories with fresh task completions
        $categories = RoutineCategory::active()
            ->with([
                'routineTasks' => function ($query) use ($dayOfWeek) {
                    $query->active()
                        // ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $dayOfWeek . '"'])
                        ->whereJsonContains('days_of_week', $dayOfWeek)
                        ->orderBy('start_time');
                },
                'routineTasks.taskCompletions' => function ($query) use ($date) {
                    $query->where('completion_date', $date);
                }
            ])
            ->orderBy('sort_order')
            ->get();

        // Get routine statistics for the date
        $statistics = $this->getRoutineStatistics($date);

        // Get time-based view of the day
        $timelineView = $this->getTimelineView($categories, $date);

        return view('routine.show', compact(
            'dailyLog',
            'categories',
            'statistics',
            'timelineView',
            'date'
        ));
    }

    /**
     * Update task completion status.
     */
    public function updateTaskCompletion(Request $request, TaskCompletion $taskCompletion)
    {
        $request->validate([
            'is_completed' => 'required|boolean',
            'quality_score' => 'nullable|integer|min:1|max:10',
            'actual_start_time' => 'nullable|date_format:H:i',
            'actual_end_time' => 'nullable|date_format:H:i',
            'energy_before' => 'nullable|integer|min:1|max:10',
            'energy_after' => 'nullable|integer|min:1|max:10',
            'notes' => 'nullable|string|max:500',
            'obstacles' => 'nullable|string|max:500',
            'improvements' => 'nullable|string|max:500',
        ]);

        $data = $request->only([
            'is_completed',
            'quality_score',
            'actual_start_time',
            'actual_end_time',
            'energy_before',
            'energy_after',
            'notes',
            'obstacles',
            'improvements'
        ]);

        // Set completion status based on completion state
        if ($request->is_completed) {
            $data['completion_status'] = 'completed';
            $data['actual_end_time'] = $data['actual_end_time'] ?? now()->format('H:i');
        } else {
            $data['completion_status'] = 'not_started';
        }

        $taskCompletion->update($data);

        // Calculate actual duration if both times are provided
        if ($taskCompletion->actual_start_time && $taskCompletion->actual_end_time) {
            $taskCompletion->update([
                'actual_duration' => $taskCompletion->calculateActualDuration()
            ]);
        }

        return response()->json([
            'success' => true,
            'task_completion' => $taskCompletion->fresh(['routineTask']),
            'performance_summary' => $taskCompletion->getPerformanceSummary(),
        ]);
    }

    /**
     * Start a task (mark as in progress).
     */
    public function startTask(Request $request, TaskCompletion $taskCompletion)
    {
        $request->validate([
            'energy_before' => 'nullable|integer|min:1|max:10',
        ]);

        $taskCompletion->update([
            'completion_status' => 'in_progress',
            'actual_start_time' => now()->format('H:i'),
            'energy_before' => $request->energy_before,
        ]);

        return response()->json([
            'success' => true,
            'task_completion' => $taskCompletion->fresh(),
        ]);
    }

    /**
     * Complete a task with quality rating.
     */
    public function completeTask(Request $request, TaskCompletion $taskCompletion)
    {
        $request->validate([
            'quality_score' => 'required|integer|min:1|max:10',
            'energy_after' => 'nullable|integer|min:1|max:10',
            'notes' => 'nullable|string|max:500',
        ]);

        $taskCompletion->update([
            'is_completed' => true,
            'completion_status' => 'completed',
            'actual_end_time' => now()->format('H:i'),
            'quality_score' => $request->quality_score,
            'energy_after' => $request->energy_after,
            'notes' => $request->notes,
        ]);

        // Calculate duration
        if ($taskCompletion->actual_start_time) {
            $taskCompletion->update([
                'actual_duration' => $taskCompletion->calculateActualDuration()
            ]);
        }

        return response()->json([
            'success' => true,
            'task_completion' => $taskCompletion->fresh(),
            'message' => 'Task completed successfully!',
        ]);
    }

    /**
     * Skip a task with reason.
     */
    public function skipTask(Request $request, TaskCompletion $taskCompletion)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $taskCompletion->update([
            'completion_status' => 'skipped',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'task_completion' => $taskCompletion->fresh(),
        ]);
    }

    /**
     * Postpone a task.
     */
    public function postponeTask(Request $request, TaskCompletion $taskCompletion)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $taskCompletion->update([
            'completion_status' => 'postponed',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'task_completion' => $taskCompletion->fresh(),
        ]);
    }

    /**
     * Get routine statistics for a specific date.
     */
    private function getRoutineStatistics($date)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Get all scheduled tasks for the date
        $scheduledTasks = RoutineTask::active()
            // ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $dayOfWeek . '"'])
            ->whereJsonContains('days_of_week', $dayOfWeek)
            ->with(['taskCompletions' => function ($query) use ($date) {
                $query->where('completion_date', $date);
            }])
            ->get();

        $totalTasks = $scheduledTasks->count();
        $completedTasks = $scheduledTasks->filter(function ($task) {
            return $task->taskCompletions->where('is_completed', true)->count() > 0;
        })->count();

        $inProgressTasks = $scheduledTasks->filter(function ($task) {
            return $task->taskCompletions->where('completion_status', 'in_progress')->count() > 0;
        })->count();

        $skippedTasks = $scheduledTasks->filter(function ($task) {
            return $task->taskCompletions->where('completion_status', 'skipped')->count() > 0;
        })->count();

        // Calculate average quality score
        $qualityScores = TaskCompletion::where('completion_date', $date)
            ->where('is_completed', true)
            ->whereNotNull('quality_score')
            ->pluck('quality_score');

        $averageQuality = $qualityScores->count() > 0 ? round($qualityScores->avg(), 1) : 0;

        // Calculate total actual time spent
        $totalActualTime = TaskCompletion::where('completion_date', $date)
            ->where('is_completed', true)
            ->whereNotNull('actual_duration')
            ->sum('actual_duration');

        // Calculate total scheduled time
        $totalScheduledTime = $scheduledTasks->sum('estimated_duration');

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'pending_tasks' => $totalTasks - $completedTasks - $inProgressTasks - $skippedTasks,
            'skipped_tasks' => $skippedTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0,
            'average_quality' => $averageQuality,
            'total_actual_time' => $totalActualTime,
            'total_scheduled_time' => $totalScheduledTime,
            'time_variance' => $totalActualTime - $totalScheduledTime,
        ];
    }

    /**
     * Get timeline view of the day.
     */
    private function getTimelineView($categories, $date)
    {
        $timeline = collect();

        foreach ($categories as $category) {
            foreach ($category->routineTasks as $task) {
                $completion = $task->taskCompletions->first();

                $timeline->push([
                    'time' => $task->start_time->format('H:i'),
                    'end_time' => $task->end_time->format('H:i'),
                    'title' => $task->title,
                    'category' => $category->name,
                    'category_color' => $category->color,
                    'priority' => $task->priority,
                    'status' => $completion?->completion_status ?? 'not_started',
                    'is_completed' => $completion?->is_completed ?? false,
                    'quality_score' => $completion?->quality_score,
                    'task_id' => $task->id,
                    'completion_id' => $completion?->id,
                    'is_flexible' => $task->is_flexible,
                    'estimated_duration' => $task->estimated_duration,
                    'actual_duration' => $completion?->actual_duration,
                ]);
            }
        }

        return $timeline->sortBy('time')->values();
    }

    /**
     * Ensure task completions exist for all scheduled tasks.
     */
    private function ensureTaskCompletions($categories, $dailyLog, $date)
    {
        foreach ($categories as $category) {
            foreach ($category->routineTasks as $task) {
                $existingCompletion = $task->taskCompletions->first();

                if (!$existingCompletion) {
                    TaskCompletion::create([
                        'routine_task_id' => $task->id,
                        'daily_log_id' => $dailyLog->id,
                        'completion_date' => $date,
                        'is_completed' => false,
                        'completion_status' => 'not_started',
                    ]);
                }
            }
        }
    }

    /**
     * Get routine data for a specific date range (for analytics).
     */
    public function getRoutineData(Request $request)
    {
        $startDate = $request->get('start_date', today()->subDays(7));
        $endDate = $request->get('end_date', today());

        $data = collect();

        for ($date = Carbon::parse($startDate); $date->lte(Carbon::parse($endDate)); $date->addDay()) {
            $statistics = $this->getRoutineStatistics($date->toDateString());

            $data->push([
                'date' => $date->format('Y-m-d'),
                'date_formatted' => $date->format('M j'),
                'day_name' => $date->format('l'),
                'statistics' => $statistics,
            ]);
        }

        return response()->json($data);
    }

    /**
     * Bulk update multiple task completions.
     */
    public function bulkUpdateTasks(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.completion_id' => 'required|exists:task_completions,id',
            'updates.*.is_completed' => 'required|boolean',
            'updates.*.quality_score' => 'nullable|integer|min:1|max:10',
        ]);

        $results = [];

        foreach ($request->updates as $update) {
            $completion = TaskCompletion::find($update['completion_id']);

            $completion->update([
                'is_completed' => $update['is_completed'],
                'quality_score' => $update['quality_score'] ?? null,
                'completion_status' => $update['is_completed'] ? 'completed' : 'not_started',
            ]);

            $results[] = [
                'completion_id' => $completion->id,
                'success' => true,
            ];
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Export routine data for a date range.
     */
    public function exportData(Request $request)
    {
        $startDate = $request->get('start_date', today()->subDays(30));
        $endDate = $request->get('end_date', today());

        $completions = TaskCompletion::with(['routineTask', 'routineTask.routineCategory'])
            ->forDateRange($startDate, $endDate)
            ->get();

        $export = $completions->map(function ($completion) {
            return [
                'Date' => $completion->completion_date->format('Y-m-d'),
                'Category' => $completion->routineTask->routineCategory->name,
                'Task' => $completion->routineTask->title,
                'Scheduled Time' => $completion->routineTask->start_time->format('H:i') . ' - ' . $completion->routineTask->end_time->format('H:i'),
                'Status' => $completion->completion_status_label,
                'Completed' => $completion->is_completed ? 'Yes' : 'No',
                'Quality Score' => $completion->quality_score ?? 'N/A',
                'Actual Duration (min)' => $completion->actual_duration ?? 'N/A',
                'Energy Before' => $completion->energy_before ?? 'N/A',
                'Energy After' => $completion->energy_after ?? 'N/A',
                'Notes' => $completion->notes ?? '',
            ];
        });

        return response()->json([
            'filename' => 'routine_data_' . $startDate . '_to_' . $endDate . '.csv',
            'data' => $export,
        ]);
    }

    /**
     * Debug method to check all tasks for a specific date.
     */
    public function debugTasks(Request $request)
    {
        $date = $request->get('date', today());
        $parsedDate = Carbon::parse($date)->toDateString();
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $dayName = Carbon::parse($date)->format('l');

        // Get all categories
        $allCategories = RoutineCategory::withCount('routineTasks')->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'is_active' => $cat->is_active,
                'sort_order' => $cat->sort_order,
                'task_count' => $cat->routine_tasks_count,
            ];
        });

        // Get all active tasks
        $allTasks = RoutineTask::with('routineCategory')->get()->map(function ($task) use ($dayOfWeek) {
            $scheduledToday = $task->is_active && in_array($dayOfWeek, $task->days_of_week ?? []);
            return [
                'id' => $task->id,
                'title' => $task->title,
                'category' => $task->routineCategory->name,
                'days_of_week' => $task->days_of_week,
                'is_active' => $task->is_active,
                'scheduled_today' => $scheduledToday,
                'start_time' => $task->start_time->format('H:i'),
                'end_time' => $task->end_time->format('H:i'),
            ];
        });

        // Get tasks scheduled for this day
        $scheduledTasks = RoutineTask::active()
            // ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $dayOfWeek . '"'])
            ->whereJsonContains('days_of_week', $dayOfWeek)
            ->with('routineCategory')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'category' => $task->routineCategory->name,
                    'start_time' => $task->start_time->format('H:i'),
                    'end_time' => $task->end_time->format('H:i'),
                    'days_of_week' => $task->days_of_week,
                    'is_active' => $task->is_active,
                ];
            });

        // Get existing completions
        $existingCompletions = TaskCompletion::where('completion_date', $parsedDate)
            ->with('routineTask')
            ->get()
            ->map(function ($completion) {
                return [
                    'id' => $completion->id,
                    'task_title' => $completion->routineTask->title,
                    'is_completed' => $completion->is_completed,
                    'status' => $completion->completion_status,
                    'quality_score' => $completion->quality_score,
                ];
            });

        // Return debug view
        return view('debug-tasks', compact(
            'date',
            'parsedDate',
            'dayOfWeek',
            'dayName',
            'allCategories',
            'allTasks',
            'scheduledTasks',
            'existingCompletions'
        ));
    }
}