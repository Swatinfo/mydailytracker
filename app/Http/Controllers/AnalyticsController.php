<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\RoutineCategory;
use App\Models\RoutineTask;
use App\Models\TaskCompletion;
use App\Models\Book;
use App\Models\ReadingSession;
use App\Models\WeeklyReview;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // Default to 30 days
        $endDate = today();
        $startDate = today()->subDays((int)$period - 1);

        // Get comprehensive analytics data
        $analytics = [
            'overview' => $this->getOverviewAnalytics($startDate, $endDate),
            'performance_trends' => $this->getPerformanceTrends($startDate, $endDate),
            'category_analysis' => $this->getCategoryAnalysis($startDate, $endDate),
            'task_insights' => $this->getTaskInsights($startDate, $endDate),
            'reading_analytics' => $this->getReadingAnalytics($startDate, $endDate),
            'energy_patterns' => $this->getEnergyPatterns($startDate, $endDate),
            'productivity_insights' => $this->getProductivityInsights($startDate, $endDate),
            'weekly_patterns' => $this->getWeeklyPatterns($startDate, $endDate),
            'goal_tracking' => $this->getGoalTracking($startDate, $endDate),
        ];

        return view('analytics.index', compact('analytics', 'period', 'startDate', 'endDate'));
    }

    /**
     * Get overview analytics.
     */
    private function getOverviewAnalytics($startDate, $endDate)
    {
        $logs = DailyLog::forDateRange($startDate, $endDate)->get();
        $totalDays = $logs->count();

        if ($totalDays === 0) {
            return $this->getEmptyOverview();
        }

        // Calculate averages
        $avgCompletionRate = round($logs->avg('completion_rate'), 1);
        $avgQualityScore = round($logs->whereNotNull('average_quality_score')->avg('average_quality_score'), 1);
        $avgSatisfaction = round($logs->whereNotNull('overall_satisfaction')->avg('overall_satisfaction'), 1);
        $avgStressLevel = round($logs->whereNotNull('stress_level')->avg('stress_level'), 1);

        // Calculate totals
        $totalReadingTime = $logs->sum('total_reading_time');
        $totalPagesRead = $logs->sum('total_pages_read');
        $exerciseDays = $logs->where('exercise_completed', true)->count();

        // Calculate consistency metrics
        $excellentDays = $logs->where('completion_rate', '>=', 85)->count(); // CEO target: 85%+
        $qualityDays = $logs->where('average_quality_score', '>=', 8)->count(); // Target: 8+
        $readingDays = $logs->where('total_reading_time', '>=', 30)->count(); // Target: 30 min

        return [
            'total_days' => $totalDays,
            'avg_completion_rate' => $avgCompletionRate,
            'avg_quality_score' => $avgQualityScore,
            'avg_satisfaction' => $avgSatisfaction,
            'avg_stress_level' => $avgStressLevel,
            'total_reading_time' => $totalReadingTime,
            'avg_daily_reading' => round($totalReadingTime / $totalDays, 1),
            'total_pages_read' => $totalPagesRead,
            'exercise_days' => $exerciseDays,
            'exercise_consistency' => round(($exerciseDays / $totalDays) * 100, 1),
            'excellent_days' => $excellentDays,
            'excellence_rate' => round(($excellentDays / $totalDays) * 100, 1),
            'quality_days' => $qualityDays,
            'quality_consistency' => round(($qualityDays / $totalDays) * 100, 1),
            'reading_days' => $readingDays,
            'reading_consistency' => round(($readingDays / $totalDays) * 100, 1),
        ];
    }

    /**
     * Get performance trends over time.
     */
    private function getPerformanceTrends($startDate, $endDate)
    {
        $logs = DailyLog::forDateRange($startDate, $endDate)
            ->orderBy('log_date')
            ->get();

        $trends = $logs->map(function ($log) {
            return [
                'date' => $log->log_date->format('Y-m-d'),
                'date_formatted' => $log->log_date->format('M j'),
                'completion_rate' => $log->completion_rate,
                'quality_score' => $log->average_quality_score ?? 0,
                'satisfaction' => $log->overall_satisfaction ?? 0,
                'stress_level' => $log->stress_level ?? 0,
                'reading_time' => $log->total_reading_time,
                'energy_morning' => $log->morning_energy_level ?? 0,
                'energy_evening' => $log->evening_energy_level ?? 0,
                'targets_met' => $log->targets_met_count,
            ];
        });

        // Calculate moving averages (7-day)
        $movingAverages = $this->calculateMovingAverages($trends, 7);

        return [
            'daily_data' => $trends,
            'moving_averages' => $movingAverages,
            'trend_analysis' => $this->analyzeTrends($trends),
        ];
    }

    /**
     * Get category performance analysis.
     */
    private function getCategoryAnalysis($startDate, $endDate)
    {
        $categories = RoutineCategory::active()->get();

        return $categories->map(function ($category) use ($startDate, $endDate) {
            // Get all tasks for this category
            $taskIds = $category->routineTasks()->active()->pluck('id');

            if ($taskIds->isEmpty()) {
                return [
                    'name' => $category->name,
                    'color' => $category->color,
                    'completion_rate' => 0,
                    'quality_score' => 0,
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'avg_duration' => 0,
                    'consistency' => 0,
                ];
            }

            // Get completions for the date range
            $completions = TaskCompletion::whereIn('routine_task_id', $taskIds)
                ->forDateRange($startDate, $endDate)
                ->get();

            $totalCompletions = $completions->count();
            $successfulCompletions = $completions->where('is_completed', true)->count();
            $avgDuration = $completions->where('is_completed', true)->avg('actual_duration');
            $avgQuality = $completions->where('is_completed', true)->whereNotNull('quality_score')->avg('quality_score');

            // Calculate completion rate based on scheduled vs completed
            $period = \Carbon\Carbon::parse($startDate)->daysUntil(\Carbon\Carbon::parse($endDate));
            $totalScheduled = 0;
            $totalCompleted = 0;

            foreach ($period as $date) {
                $dayOfWeek = $date->dayOfWeek;
                $dayTaskIds = $category->routineTasks()
                    ->active()
                    ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $dayOfWeek . '"'])
                    ->pluck('id');

                $totalScheduled += $dayTaskIds->count();
                $totalCompleted += TaskCompletion::whereIn('routine_task_id', $dayTaskIds)
                    ->where('completion_date', $date->toDateString())
                    ->where('is_completed', true)
                    ->count();
            }

            $completionRate = $totalScheduled > 0 ? round(($totalCompleted / $totalScheduled) * 100, 1) : 0;

            return [
                'name' => $category->name,
                'color' => $category->color,
                'completion_rate' => $completionRate,
                'quality_score' => $avgQuality ? round($avgQuality, 1) : 0,
                'total_tasks' => $totalCompletions,
                'completed_tasks' => $successfulCompletions,
                'avg_duration' => $avgDuration ? round($avgDuration, 1) : 0,
                'consistency' => $totalCompletions > 0 ? round(($successfulCompletions / $totalCompletions) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get task-level insights.
     */
    private function getTaskInsights($startDate, $endDate)
    {
        // Top performing tasks
        $topTasks = RoutineTask::active()
            ->with(['taskCompletions' => function ($query) use ($startDate, $endDate) {
                $query->forDateRange($startDate, $endDate)->where('is_completed', true);
            }])
            ->get()
            ->map(function ($task) use ($startDate, $endDate) {
                $completions = $task->taskCompletions;
                if ($completions->isEmpty()) return null;

                return [
                    'task' => $task->title,
                    'category' => $task->routineCategory->name,
                    'completion_rate' => $task->getCompletionRate($startDate, $endDate),
                    'avg_quality' => round($completions->avg('quality_score'), 1),
                    'total_completions' => $completions->count(),
                ];
            })
            ->filter()
            ->sortByDesc('avg_quality')
            ->take(5);

        // Tasks needing improvement
        $improvementTasks = RoutineTask::active()
            ->with(['taskCompletions' => function ($query) use ($startDate, $endDate) {
                $query->forDateRange($startDate, $endDate);
            }])
            ->get()
            ->map(function ($task) use ($startDate, $endDate) {
                $completionRate = $task->getCompletionRate($startDate, $endDate);
                if ($completionRate >= 80) return null; // Only show tasks below 80%

                return [
                    'task' => $task->title,
                    'category' => $task->routineCategory->name,
                    'completion_rate' => $completionRate,
                    'priority' => $task->priority_label,
                    'issues' => $this->identifyTaskIssues($task, $startDate, $endDate),
                ];
            })
            ->filter()
            ->sortBy('completion_rate')
            ->take(5);

        return [
            'top_performers' => $topTasks,
            'needs_improvement' => $improvementTasks,
            'time_analysis' => $this->getTimeAnalysis($startDate, $endDate),
        ];
    }

    /**
     * Get reading analytics.
     */
    private function getReadingAnalytics($startDate, $endDate)
    {
        $sessions = ReadingSession::forDateRange($startDate, $endDate)->get();
        $totalSessions = $sessions->count();

        if ($totalSessions === 0) {
            return $this->getEmptyReadingAnalytics();
        }

        $totalTime = $sessions->sum('duration_minutes');
        $totalPages = $sessions->sum('pages_read');
        $avgSessionDuration = round($totalTime / $totalSessions, 1);
        $avgPagesPerSession = round($totalPages / $totalSessions, 1);
        $avgReadingSpeed = $totalTime > 0 ? round($totalPages / $totalTime, 2) : 0;

        // Books progress
        $booksRead = Book::whereHas('readingSessions', function ($query) use ($startDate, $endDate) {
            $query->forDateRange($startDate, $endDate);
        })->get();

        $completedBooks = Book::completed()
            ->whereBetween('completed_date', [$startDate, $endDate])
            ->count();

        // Reading patterns
        $dailyReadingTime = $sessions->groupBy('session_date')
            ->map(function ($daySessions) {
                return $daySessions->sum('duration_minutes');
            });

        $readingDays = $dailyReadingTime->count();
        $consistency = round(($readingDays / $startDate->diffInDays($endDate) + 1) * 100, 1);

        return [
            'total_sessions' => $totalSessions,
            'total_time' => $totalTime,
            'total_pages' => $totalPages,
            'avg_session_duration' => $avgSessionDuration,
            'avg_pages_per_session' => $avgPagesPerSession,
            'avg_reading_speed' => $avgReadingSpeed,
            'books_in_progress' => $booksRead->where('status', 'currently_reading')->count(),
            'books_completed' => $completedBooks,
            'reading_days' => $readingDays,
            'consistency' => $consistency,
            'daily_pattern' => $this->getReadingDailyPattern($sessions),
            'category_breakdown' => $this->getReadingCategoryBreakdown($booksRead),
        ];
    }

    /**
     * Get energy patterns analysis.
     */
    private function getEnergyPatterns($startDate, $endDate)
    {
        $logs = DailyLog::forDateRange($startDate, $endDate)
            ->whereNotNull('morning_energy_level')
            ->whereNotNull('evening_energy_level')
            ->get();

        if ($logs->isEmpty()) {
            return $this->getEmptyEnergyPatterns();
        }

        $avgMorningEnergy = round($logs->avg('morning_energy_level'), 1);
        $avgEveningEnergy = round($logs->avg('evening_energy_level'), 1);
        $avgEnergyChange = round($logs->avg('energy_change'), 1);

        // Energy by day of week
        $energyByDay = $logs->groupBy(function ($log) {
            return $log->log_date->dayOfWeek;
        })->map(function ($dayLogs) {
            return [
                'morning' => round($dayLogs->avg('morning_energy_level'), 1),
                'evening' => round($dayLogs->avg('evening_energy_level'), 1),
                'change' => round($dayLogs->avg('energy_change'), 1),
            ];
        });

        return [
            'avg_morning_energy' => $avgMorningEnergy,
            'avg_evening_energy' => $avgEveningEnergy,
            'avg_energy_change' => $avgEnergyChange,
            'energy_stability' => $this->calculateEnergyStability($logs),
            'energy_by_day' => $energyByDay,
            'energy_trends' => $this->getEnergyTrends($logs),
        ];
    }

    /**
     * Get productivity insights.
     */
    private function getProductivityInsights($startDate, $endDate)
    {
        $completions = TaskCompletion::forDateRange($startDate, $endDate)
            ->where('is_completed', true)
            ->with('routineTask')
            ->get();

        // Peak productivity hours
        $hourlyProductivity = $completions->groupBy(function ($completion) {
            return $completion->actual_start_time ?
                Carbon::parse($completion->actual_start_time)->hour :
                Carbon::parse($completion->routineTask->start_time)->hour;
        })->map(function ($hourCompletions) {
            return [
                'count' => $hourCompletions->count(),
                'avg_quality' => round($hourCompletions->avg('quality_score'), 1),
            ];
        });

        // Most productive days
        $dailyProductivity = $completions->groupBy('completion_date')
            ->map(function ($dayCompletions, $date) {
                return [
                    'date' => $date,
                    'tasks_completed' => $dayCompletions->count(),
                    'avg_quality' => round($dayCompletions->avg('quality_score'), 1),
                    'total_time' => $dayCompletions->sum('actual_duration'),
                ];
            })
            ->sortByDesc('avg_quality')
            ->take(5);

        return [
            'peak_hours' => $hourlyProductivity,
            'most_productive_days' => $dailyProductivity,
            'quality_distribution' => $this->getQualityDistribution($completions),
            'time_efficiency' => $this->getTimeEfficiency($completions),
        ];
    }

    /**
     * Get weekly patterns analysis.
     */
    private function getWeeklyPatterns($startDate, $endDate)
    {
        $logs = DailyLog::forDateRange($startDate, $endDate)->get();

        $weeklyData = $logs->groupBy(function ($log) {
            return $log->log_date->dayOfWeek;
        })->map(function ($dayLogs, $dayOfWeek) {
            $dayName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$dayOfWeek];

            return [
                'day' => $dayName,
                'avg_completion' => round($dayLogs->avg('completion_rate'), 1),
                'avg_quality' => round($dayLogs->whereNotNull('average_quality_score')->avg('average_quality_score'), 1),
                'avg_satisfaction' => round($dayLogs->whereNotNull('overall_satisfaction')->avg('overall_satisfaction'), 1),
                'avg_reading_time' => round($dayLogs->avg('total_reading_time'), 1),
                'days_count' => $dayLogs->count(),
            ];
        });

        return [
            'weekly_patterns' => $weeklyData,
            'best_day' => $weeklyData->sortByDesc('avg_completion')->first(),
            'most_challenging_day' => $weeklyData->sortBy('avg_completion')->first(),
        ];
    }

    /**
     * Get goal tracking data.
     */
    private function getGoalTracking($startDate, $endDate)
    {
        $logs = DailyLog::forDateRange($startDate, $endDate)->get();
        $totalDays = $logs->count();

        if ($totalDays === 0) {
            return $this->getEmptyGoalTracking();
        }

        // CEO Excellence Targets
        $targets = [
            'completion_rate' => ['target' => 85, 'achieved' => $logs->where('completion_rate', '>=', 85)->count()],
            'quality_score' => ['target' => 8, 'achieved' => $logs->where('average_quality_score', '>=', 8)->count()],
            'reading_time' => ['target' => 30, 'achieved' => $logs->where('total_reading_time', '>=', 30)->count()],
            'exercise' => ['target' => 1, 'achieved' => $logs->where('exercise_completed', true)->count()],
            'satisfaction' => ['target' => 8, 'achieved' => $logs->where('overall_satisfaction', '>=', 8)->count()],
        ];

        $overallTargets = collect($targets)->map(function ($target, $key) use ($totalDays) {
            $achievementRate = round(($target['achieved'] / $totalDays) * 100, 1);
            return [
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'target' => $target['target'],
                'achieved_days' => $target['achieved'],
                'total_days' => $totalDays,
                'achievement_rate' => $achievementRate,
                'status' => $achievementRate >= 85 ? 'excellent' : ($achievementRate >= 70 ? 'good' : 'needs_improvement'),
            ];
        });

        return [
            'targets' => $overallTargets,
            'overall_score' => round($overallTargets->avg('achievement_rate'), 1),
            'streak_analysis' => $this->getStreakAnalysis($logs),
        ];
    }

    /**
     * Helper methods
     */
    private function getEmptyOverview()
    {
        return [
            'total_days' => 0,
            'avg_completion_rate' => 0,
            'avg_quality_score' => 0,
            'avg_satisfaction' => 0,
            'avg_stress_level' => 0,
            'total_reading_time' => 0,
            'avg_daily_reading' => 0,
            'total_pages_read' => 0,
            'exercise_days' => 0,
            'exercise_consistency' => 0,
            'excellent_days' => 0,
            'excellence_rate' => 0,
            'quality_days' => 0,
            'quality_consistency' => 0,
            'reading_days' => 0,
            'reading_consistency' => 0,
        ];
    }

    private function getEmptyReadingAnalytics()
    {
        return [
            'total_sessions' => 0,
            'total_time' => 0,
            'total_pages' => 0,
            'avg_session_duration' => 0,
            'avg_pages_per_session' => 0,
            'avg_reading_speed' => 0,
            'books_in_progress' => 0,
            'books_completed' => 0,
            'reading_days' => 0,
            'consistency' => 0,
            'daily_pattern' => [],
            'category_breakdown' => [],
        ];
    }

    private function getEmptyEnergyPatterns()
    {
        return [
            'avg_morning_energy' => 0,
            'avg_evening_energy' => 0,
            'avg_energy_change' => 0,
            'energy_stability' => 0,
            'energy_by_day' => [],
            'energy_trends' => [],
        ];
    }

    private function getEmptyGoalTracking()
    {
        return [
            'targets' => [],
            'overall_score' => 0,
            'streak_analysis' => [],
        ];
    }

    private function identifyTaskIssues($task, $startDate, $endDate)
    {
        $completions = $task->taskCompletions()
            ->forDateRange($startDate, $endDate)
            ->get();

        $issues = [];

        $skippedCount = $completions->where('completion_status', 'skipped')->count();
        $postponedCount = $completions->where('completion_status', 'postponed')->count();
        $lowQualityCount = $completions->where('quality_score', '<', 6)->count();

        if ($skippedCount > 2) {
            $issues[] = "Frequently skipped ({$skippedCount} times)";
        }

        if ($postponedCount > 2) {
            $issues[] = "Often postponed ({$postponedCount} times)";
        }

        if ($lowQualityCount > 1) {
            $issues[] = "Low quality scores ({$lowQualityCount} sessions)";
        }

        return $issues;
    }

    private function getTimeAnalysis($startDate, $endDate)
    {
        $completions = TaskCompletion::forDateRange($startDate, $endDate)
            ->where('is_completed', true)
            ->whereNotNull('actual_duration')
            ->with('routineTask')
            ->get();

        $totalScheduled = $completions->sum(function ($completion) {
            return $completion->routineTask->estimated_duration;
        });

        $totalActual = $completions->sum('actual_duration');
        $variance = $totalActual - $totalScheduled;
        $variancePercentage = $totalScheduled > 0 ? round(($variance / $totalScheduled) * 100, 1) : 0;

        return [
            'total_scheduled_time' => $totalScheduled,
            'total_actual_time' => $totalActual,
            'time_variance' => $variance,
            'variance_percentage' => $variancePercentage,
            'efficiency_score' => $variance <= 0 ? 100 : max(0, 100 - abs($variancePercentage)),
        ];
    }

    private function getReadingDailyPattern($sessions)
    {
        $pattern = $sessions->groupBy(function ($session) {
            return $session->session_date->dayOfWeek;
        })->map(function ($daySessions) {
            return [
                'sessions' => $daySessions->count(),
                'total_time' => $daySessions->sum('duration_minutes'),
                'avg_pages' => round($daySessions->avg('pages_read'), 1),
            ];
        });

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $result = [];

        for ($i = 0; $i < 7; $i++) {
            $result[$dayNames[$i]] = $pattern->get($i, [
                'sessions' => 0,
                'total_time' => 0,
                'avg_pages' => 0,
            ]);
        }

        return $result;
    }

    private function getReadingCategoryBreakdown($books)
    {
        return $books->groupBy('category')->map(function ($categoryBooks, $category) {
            return [
                'category' => ucfirst(str_replace('_', ' ', $category)),
                'books_count' => $categoryBooks->count(),
                'total_pages' => $categoryBooks->sum('total_pages'),
                'completed_books' => $categoryBooks->where('status', 'completed')->count(),
            ];
        })->values();
    }

    private function calculateEnergyStability($logs)
    {
        $changes = $logs->map('energy_change')->filter()->values();
        if ($changes->isEmpty()) return 0;

        $avgChange = $changes->avg();
        $variance = $changes->map(function ($change) use ($avgChange) {
            return pow($change - $avgChange, 2);
        })->avg();

        $stability = max(0, 100 - ($variance * 10)); // Higher variance = lower stability
        return round($stability, 1);
    }

    private function getEnergyTrends($logs)
    {
        return $logs->map(function ($log) {
            return [
                'date' => $log->log_date->format('M j'),
                'morning' => $log->morning_energy_level,
                'evening' => $log->evening_energy_level,
                'change' => $log->energy_change,
            ];
        })->values();
    }

    private function getQualityDistribution($completions)
    {
        $distribution = [];
        for ($i = 1; $i <= 10; $i++) {
            $count = $completions->where('quality_score', $i)->count();
            $distribution[] = [
                'score' => $i,
                'count' => $count,
                'percentage' => $completions->count() > 0 ? round(($count / $completions->count()) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    private function getTimeEfficiency($completions)
    {
        $onTimeCount = $completions->filter(function ($completion) {
            return $completion->isOnTime();
        })->count();

        $earlyCount = $completions->filter(function ($completion) {
            if (!$completion->actual_duration || !$completion->routineTask) return false;
            return $completion->actual_duration < $completion->routineTask->estimated_duration;
        })->count();

        $lateCount = $completions->filter(function ($completion) {
            if (!$completion->actual_duration || !$completion->routineTask) return false;
            return $completion->actual_duration > $completion->routineTask->estimated_duration;
        })->count();

        $total = $completions->count();

        return [
            'on_time_percentage' => $total > 0 ? round(($onTimeCount / $total) * 100, 1) : 0,
            'early_percentage' => $total > 0 ? round(($earlyCount / $total) * 100, 1) : 0,
            'late_percentage' => $total > 0 ? round(($lateCount / $total) * 100, 1) : 0,
            'efficiency_score' => $total > 0 ? round((($onTimeCount + $earlyCount) / $total) * 100, 1) : 0,
        ];
    }

    private function getStreakAnalysis($logs)
    {
        $streaks = [
            'completion' => $this->calculateStreak($logs, fn($log) => $log->completion_rate >= 85),
            'quality' => $this->calculateStreak($logs, fn($log) => $log->average_quality_score >= 8),
            'reading' => $this->calculateStreak($logs, fn($log) => $log->total_reading_time >= 30),
            'exercise' => $this->calculateStreak($logs, fn($log) => $log->exercise_completed),
        ];

        return $streaks;
    }

    private function calculateStreak($logs, $condition)
    {
        $currentStreak = 0;
        $longestStreak = 0;

        foreach ($logs->sortBy('log_date') as $log) {
            if ($condition($log)) {
                $currentStreak++;
                $longestStreak = max($longestStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        return [
            'current' => $currentStreak,
            'longest' => $longestStreak,
        ];
    }

    private function calculateMovingAverages($trends, $window)
    {
        $data = collect($trends);
        $averages = [];

        for ($i = $window - 1; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $window + 1, $window);
            $averages[] = [
                'date' => $data[$i]['date'],
                'completion_rate' => round($subset->avg('completion_rate'), 1),
                'quality_score' => round($subset->avg('quality_score'), 1),
                'satisfaction' => round($subset->avg('satisfaction'), 1),
            ];
        }

        return $averages;
    }

    private function analyzeTrends($trends)
    {
        $data = collect($trends);
        if ($data->count() < 2) return null;

        $first = $data->first();
        $last = $data->last();

        return [
            'completion_trend' => $last['completion_rate'] - $first['completion_rate'],
            'quality_trend' => $last['quality_score'] - $first['quality_score'],
            'satisfaction_trend' => $last['satisfaction'] - $first['satisfaction'],
        ];
    }

    /**
     * Get analytics data as JSON for AJAX requests.
     */
    public function getData(Request $request)
    {
        $type = $request->get('type');
        $period = $request->get('period', 30);
        $endDate = today();
        $startDate = today()->subDays($period - 1);

        return match ($type) {
            'overview' => response()->json($this->getOverviewAnalytics($startDate, $endDate)),
            'trends' => response()->json($this->getPerformanceTrends($startDate, $endDate)),
            'categories' => response()->json($this->getCategoryAnalysis($startDate, $endDate)),
            'reading' => response()->json($this->getReadingAnalytics($startDate, $endDate)),
            'energy' => response()->json($this->getEnergyPatterns($startDate, $endDate)),
            'productivity' => response()->json($this->getProductivityInsights($startDate, $endDate)),
            'goals' => response()->json($this->getGoalTracking($startDate, $endDate)),
            default => response()->json(['error' => 'Invalid data type']),
        };
    }

    /**
     * Export analytics data.
     */
    public function exportAnalytics(Request $request)
    {
        $period = $request->get('period', 30);
        $endDate = today();
        $startDate = today()->subDays($period - 1);

        $analytics = [
            'overview' => $this->getOverviewAnalytics($startDate, $endDate),
            'performance_trends' => $this->getPerformanceTrends($startDate, $endDate),
            'category_analysis' => $this->getCategoryAnalysis($startDate, $endDate),
            'task_insights' => $this->getTaskInsights($startDate, $endDate),
            'reading_analytics' => $this->getReadingAnalytics($startDate, $endDate),
            'energy_patterns' => $this->getEnergyPatterns($startDate, $endDate),
            'productivity_insights' => $this->getProductivityInsights($startDate, $endDate),
            'weekly_patterns' => $this->getWeeklyPatterns($startDate, $endDate),
            'goal_tracking' => $this->getGoalTracking($startDate, $endDate),
        ];

        $filename = 'ceo_analytics_' . $startDate->format('Y_m_d') . '_to_' . $endDate->format('Y_m_d') . '.json';

        return response()->json($analytics)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}