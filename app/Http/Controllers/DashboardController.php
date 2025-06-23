<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\RoutineCategory;
use App\Models\RoutineTask;
use App\Models\TaskCompletion;
use App\Models\Book;
use App\Models\ReadingSession;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard overview.
     */
    public function index()
    {
        // Get or create today's daily log
        $todayLog = DailyLog::getOrCreateToday();

        // Get today's statistics
        $todayStats = $this->getTodayStatistics();

        // Get weekly performance
        $weeklyStats = $this->getWeeklyStatistics();

        // Get category performance for today
        $categoryPerformance = $this->getCategoryPerformance();

        // Get current reading progress
        $readingProgress = $this->getReadingProgress();

        // Get upcoming tasks
        $upcomingTasks = $this->getUpcomingTasks();

        // Get recent achievements
        $recentAchievements = $this->getRecentAchievements();

        // Get performance trends (last 7 days)
        $performanceTrends = $this->getPerformanceTrends();

        return view('dashboard', compact(
            'todayLog',
            'todayStats',
            'weeklyStats',
            'categoryPerformance',
            'readingProgress',
            'upcomingTasks',
            'recentAchievements',
            'performanceTrends'
        ));
    }

    /**
     * Get today's statistics.
     */
    private function getTodayStatistics()
    {
        $today = today();
        $todayLog = DailyLog::where('log_date', $today)->first();

        // Get today's scheduled tasks
        $scheduledTasks = RoutineTask::active()
            ->forToday()
            ->with(['taskCompletions' => function ($query) use ($today) {
                $query->where('completion_date', $today);
            }])
            ->get();

        $totalTasks = $scheduledTasks->count();
        $completedTasks = $scheduledTasks->filter(function ($task) {
            return $task->taskCompletions->where('is_completed', true)->count() > 0;
        })->count();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        // Get average quality score for completed tasks
        $qualityScores = TaskCompletion::where('completion_date', $today)
            ->where('is_completed', true)
            ->whereNotNull('quality_score')
            ->pluck('quality_score');

        $averageQuality = $qualityScores->count() > 0 ? round($qualityScores->avg(), 1) : 0;

        // Get reading time today
        $readingTime = ReadingSession::where('session_date', $today)->sum('duration_minutes');

        // Get energy levels
        $energyData = [
            'morning' => $todayLog?->morning_energy_level,
            'evening' => $todayLog?->evening_energy_level,
            'change' => $todayLog?->energy_change
        ];

        return [
            'completion_rate' => $completionRate,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $totalTasks - $completedTasks,
            'average_quality' => $averageQuality,
            'reading_time' => $readingTime,
            'energy' => $energyData,
            'satisfaction' => $todayLog?->overall_satisfaction,
            'targets_met' => $todayLog ? $todayLog->targets_met_count : 0,
        ];
    }

    /**
     * Get weekly statistics.
     */
    private function getWeeklyStatistics()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        // Get daily completion rates for the week
        $weeklyLogs = DailyLog::forDateRange($startOfWeek, $endOfWeek)->get();

        $dailyRates = [];
        $totalReadingTime = 0;
        $totalQualityScore = 0;
        $qualityCount = 0;

        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $log = $weeklyLogs->where('log_date', $date->toDateString())->first();

            if ($log) {
                $dailyRates[] = $log->completion_rate;
                $totalReadingTime += $log->total_reading_time;

                if ($log->average_quality_score > 0) {
                    $totalQualityScore += $log->average_quality_score;
                    $qualityCount++;
                }
            } else {
                $dailyRates[] = 0;
            }
        }

        $weeklyCompletionRate = count($dailyRates) > 0 ? round(array_sum($dailyRates) / count($dailyRates), 1) : 0;
        $weeklyQualityScore = $qualityCount > 0 ? round($totalQualityScore / $qualityCount, 1) : 0;

        // Calculate consistency (how many days had >80% completion)
        $consistentDays = collect($dailyRates)->filter(fn($rate) => $rate >= 80)->count();
        $consistency = round(($consistentDays / 7) * 100, 1);

        return [
            'completion_rate' => $weeklyCompletionRate,
            'quality_score' => $weeklyQualityScore,
            'reading_time' => $totalReadingTime,
            'consistency' => $consistency,
            'daily_rates' => $dailyRates,
            'consistent_days' => $consistentDays,
        ];
    }

    /**
     * Get category performance for today.
     */
    private function getCategoryPerformance()
    {
        $categories = RoutineCategory::active()->with([
            'routineTasks' => function ($query) {
                $query->active()->forToday();
            },
            'routineTasks.taskCompletions' => function ($query) {
                $query->where('completion_date', today());
            }
        ])->get();

        return $categories->map(function ($category) {
            $tasks = $category->routineTasks;
            $totalTasks = $tasks->count();

            if ($totalTasks === 0) {
                return [
                    'name' => $category->name,
                    'color' => $category->color,
                    'completion_rate' => 0,
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                ];
            }

            $completedTasks = $tasks->filter(function ($task) {
                return $task->taskCompletions->where('is_completed', true)->count() > 0;
            })->count();

            return [
                'name' => $category->name,
                'color' => $category->color,
                'completion_rate' => round(($completedTasks / $totalTasks) * 100, 1),
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
            ];
        });
    }

    /**
     * Get current reading progress.
     */
    private function getReadingProgress()
    {
        $currentBooks = Book::currentlyReading()
            ->orderBy('priority', 'desc')
            ->with('recentReadingSessions')
            ->get();

        $todayReading = ReadingSession::today()->sum('duration_minutes');
        $weeklyReading = ReadingSession::thisWeek()->sum('duration_minutes');

        return [
            'current_books' => $currentBooks->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'progress_percentage' => $book->progress_percentage,
                    'current_page' => $book->current_page,
                    'total_pages' => $book->total_pages,
                    'pages_remaining' => $book->pages_remaining,
                    'reading_streak' => $book->getCurrentReadingStreak(),
                    'estimated_completion' => $book->estimated_completion_date?->format('M j'),
                ];
            }),
            'today_reading_time' => $todayReading,
            'weekly_reading_time' => $weeklyReading,
            'weekly_target' => 210, // 30 minutes * 7 days
            'weekly_progress' => round(($weeklyReading / 210) * 100, 1),
        ];
    }

    /**
     * Get upcoming tasks for today.
     */
    private function getUpcomingTasks()
    {
        $now = now();
        $endOfDay = now()->endOfDay();

        return RoutineTask::active()
            ->forToday()
            ->where('start_time', '>', $now->format('H:i'))
            ->where('start_time', '<=', $endOfDay->format('H:i'))
            ->with(['routineCategory', 'todaysCompletion'])
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'category' => $task->routineCategory->name,
                    'start_time' => $task->start_time->format('H:i'),
                    'end_time' => $task->end_time->format('H:i'),
                    'duration' => $task->estimated_duration,
                    'priority' => $task->priority_label,
                    'is_completed' => $task->todaysCompletion?->is_completed ?? false,
                ];
            });
    }

    /**
     * Get recent achievements.
     */
    private function getRecentAchievements()
    {
        $achievements = collect();

        // Check for recent streaks
        $recentLogs = DailyLog::recent(7)->get();
        $completionStreak = 0;

        foreach ($recentLogs->sortByDesc('log_date') as $log) {
            if ($log->completion_rate >= 85) {
                $completionStreak++;
            } else {
                break;
            }
        }

        if ($completionStreak >= 3) {
            $achievements->push([
                'type' => 'streak',
                'title' => "{$completionStreak}-day Excellence Streak",
                'description' => "Maintained 85%+ completion rate",
                'icon' => '🔥',
            ]);
        }

        // Check for recently completed books
        $recentBooks = Book::recentlyCompleted(7)->get();
        foreach ($recentBooks as $book) {
            $achievements->push([
                'type' => 'book_completed',
                'title' => 'Book Completed',
                'description' => "\"{$book->title}\" by {$book->author}",
                'icon' => '📚',
            ]);
        }

        // Check for perfect quality days
        $perfectQualityDays = $recentLogs->where('average_quality_score', '>=', 9)->count();
        if ($perfectQualityDays > 0) {
            $achievements->push([
                'type' => 'quality',
                'title' => 'Quality Excellence',
                'description' => "{$perfectQualityDays} day(s) with 9+ average quality",
                'icon' => '⭐',
            ]);
        }

        return $achievements->take(3);
    }

    /**
     * Get performance trends for the last 7 days.
     */
    private function getPerformanceTrends()
    {
        $endDate = today();
        $startDate = today()->subDays(6);

        $logs = DailyLog::forDateRange($startDate, $endDate)
            ->orderBy('log_date')
            ->get();

        $trends = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $log = $logs->where('log_date', $date->toDateString())->first();

            $trends[] = [
                'date' => $date->format('M j'),
                'completion_rate' => $log?->completion_rate ?? 0,
                'quality_score' => $log?->average_quality_score ?? 0,
                'reading_time' => $log?->total_reading_time ?? 0,
                'energy_morning' => $log?->morning_energy_level ?? 0,
                'energy_evening' => $log?->evening_energy_level ?? 0,
                'satisfaction' => $log?->overall_satisfaction ?? 0,
            ];
        }

        return $trends;
    }

    /**
     * Get dashboard data as JSON for AJAX requests.
     */
    public function getData(Request $request)
    {
        $type = $request->get('type', 'today');

        return match ($type) {
            'today' => response()->json($this->getTodayStatistics()),
            'weekly' => response()->json($this->getWeeklyStatistics()),
            'categories' => response()->json($this->getCategoryPerformance()),
            'reading' => response()->json($this->getReadingProgress()),
            'trends' => response()->json($this->getPerformanceTrends()),
            default => response()->json(['error' => 'Invalid data type']),
        };
    }

    /**
     * Update today's energy levels.
     */
    public function updateEnergyLevels(Request $request)
    {
        $request->validate([
            'morning_energy' => 'nullable|integer|min:1|max:10',
            'evening_energy' => 'nullable|integer|min:1|max:10',
        ]);

        $todayLog = DailyLog::getOrCreateToday();

        $todayLog->update([
            'morning_energy_level' => $request->morning_energy,
            'evening_energy_level' => $request->evening_energy,
        ]);

        return response()->json([
            'success' => true,
            'energy_change' => $todayLog->energy_change,
        ]);
    }

    /**
     * Update daily reflection.
     */
    public function updateReflection(Request $request)
    {
        $request->validate([
            'daily_reflection' => 'nullable|string|max:1000',
            'tomorrow_priorities' => 'nullable|string|max:1000',
            'overall_satisfaction' => 'nullable|integer|min:1|max:10',
        ]);

        $todayLog = DailyLog::getOrCreateToday();

        $todayLog->update($request->only([
            'daily_reflection',
            'tomorrow_priorities',
            'overall_satisfaction'
        ]));

        return response()->json(['success' => true]);
    }
}