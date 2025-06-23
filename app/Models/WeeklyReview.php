<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WeeklyReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_start_date',
        'week_end_date',
        'year',
        'week_number',
        'overall_completion_rate',
        'average_quality_score',
        'average_energy_level',
        'total_reading_minutes',
        'books_completed',
        'total_pages_read',
        'category_performance',
        'daily_completion_rates',
        'week_highlights',
        'challenges_faced',
        'lessons_learned',
        'next_week_focus',
        'goals_achieved',
        'goals_missed',
        'habits_analysis',
        'stress_level_avg',
        'satisfaction_avg',
        'exercise_consistency',
        'sleep_consistency',
        'reading_consistency',
        'improvement_areas',
        'celebration_notes',
        'metrics',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'year' => 'integer',
        'week_number' => 'integer',
        'overall_completion_rate' => 'decimal:2',
        'average_quality_score' => 'decimal:1',
        'average_energy_level' => 'decimal:1',
        'total_reading_minutes' => 'integer',
        'books_completed' => 'integer',
        'total_pages_read' => 'integer',
        'category_performance' => 'array',
        'daily_completion_rates' => 'array',
        'goals_achieved' => 'array',
        'goals_missed' => 'array',
        'habits_analysis' => 'array',
        'stress_level_avg' => 'integer',
        'satisfaction_avg' => 'integer',
        'exercise_consistency' => 'boolean',
        'sleep_consistency' => 'boolean',
        'reading_consistency' => 'boolean',
        'metrics' => 'array',
    ];

    /**
     * Get or create weekly review for a specific week.
     */
    public static function getOrCreateForWeek($date)
    {
        $carbon = Carbon::parse($date);
        $weekStart = $carbon->startOfWeek();
        $weekEnd = $carbon->copy()->endOfWeek();
        $year = $weekStart->year;
        $weekNumber = $weekStart->weekOfYear;

        return static::firstOrCreate(
            [
                'year' => $year,
                'week_number' => $weekNumber,
            ],
            [
                'week_start_date' => $weekStart,
                'week_end_date' => $weekEnd,
            ]
        );
    }

    /**
     * Get weekly review for current week.
     */
    public static function getCurrentWeek()
    {
        return static::getOrCreateForWeek(now());
    }

    /**
     * Generate weekly review from daily logs.
     */
    public function generateFromDailyLogs()
    {
        $dailyLogs = DailyLog::whereBetween('log_date', [$this->week_start_date, $this->week_end_date])
            ->get();

        if ($dailyLogs->isEmpty()) {
            return $this;
        }

        // Calculate overall metrics
        $this->overall_completion_rate = round($dailyLogs->avg('completion_rate'), 2);
        $this->average_quality_score = round($dailyLogs->whereNotNull('average_quality_score')->avg('average_quality_score'), 1);
        $this->average_energy_level = round($dailyLogs->whereNotNull('morning_energy_level')->avg('morning_energy_level'), 1);
        $this->total_reading_minutes = $dailyLogs->sum('total_reading_time');
        $this->total_pages_read = $dailyLogs->sum('total_pages_read');
        $this->stress_level_avg = round($dailyLogs->whereNotNull('stress_level')->avg('stress_level'));
        $this->satisfaction_avg = round($dailyLogs->whereNotNull('overall_satisfaction')->avg('overall_satisfaction'));

        // Calculate daily completion rates
        $this->daily_completion_rates = $dailyLogs->mapWithKeys(function ($log) {
            return [$log->log_date->format('Y-m-d') => $log->completion_rate];
        })->toArray();

        // Calculate consistency metrics
        $this->exercise_consistency = $dailyLogs->where('exercise_completed', true)->count() >= 5; // 5+ days
        $this->reading_consistency = $dailyLogs->where('total_reading_time', '>=', 30)->count() >= 6; // 6+ days
        $this->sleep_consistency = $dailyLogs->whereNotNull('sleep_quality')->where('sleep_quality', '>=', 7)->count() >= 5;

        // Calculate category performance
        $this->category_performance = $this->calculateCategoryPerformance();

        // Calculate habits analysis
        $this->habits_analysis = $this->calculateHabitsAnalysis($dailyLogs);

        // Count completed books this week
        $this->books_completed = Book::whereBetween('completed_date', [$this->week_start_date, $this->week_end_date])->count();

        $this->save();
        return $this;
    }

    /**
     * Calculate category performance for the week.
     */
    private function calculateCategoryPerformance()
    {
        $categories = RoutineCategory::active()->get();
        $performance = [];

        foreach ($categories as $category) {
            $completionRate = $category->getCompletionRateForDate($this->week_start_date); // This would need to be updated for week range
            $qualityScore = $category->getAverageQualityScore($this->week_start_date, $this->week_end_date);

            $performance[$category->slug] = [
                'name' => $category->name,
                'completion_rate' => $completionRate,
                'quality_score' => $qualityScore,
                'color' => $category->color,
            ];
        }

        return $performance;
    }

    /**
     * Calculate habits analysis.
     */
    private function calculateHabitsAnalysis($dailyLogs)
    {
        $totalDays = $dailyLogs->count();

        return [
            'daily_logging' => [
                'days_logged' => $totalDays,
                'consistency_rate' => round(($totalDays / 7) * 100, 1),
                'target' => 7,
            ],
            'exercise' => [
                'days_completed' => $dailyLogs->where('exercise_completed', true)->count(),
                'consistency_rate' => round(($dailyLogs->where('exercise_completed', true)->count() / 7) * 100, 1),
                'target' => 6,
            ],
            'reading' => [
                'days_completed' => $dailyLogs->where('total_reading_time', '>=', 30)->count(),
                'consistency_rate' => round(($dailyLogs->where('total_reading_time', '>=', 30)->count() / 7) * 100, 1),
                'target' => 7,
            ],
            'quality_tasks' => [
                'days_completed' => $dailyLogs->where('average_quality_score', '>=', 8)->count(),
                'consistency_rate' => round(($dailyLogs->where('average_quality_score', '>=', 8)->count() / 7) * 100, 1),
                'target' => 6,
            ],
            'high_completion' => [
                'days_completed' => $dailyLogs->where('completion_rate', '>=', 85)->count(),
                'consistency_rate' => round(($dailyLogs->where('completion_rate', '>=', 85)->count() / 7) * 100, 1),
                'target' => 6,
            ],
        ];
    }

    /**
     * Get week performance grade.
     */
    public function getPerformanceGradeAttribute(): string
    {
        $score = $this->getWeeklyScoreAttribute();

        return match (true) {
            $score >= 90 => 'A+',
            $score >= 85 => 'A',
            $score >= 80 => 'A-',
            $score >= 75 => 'B+',
            $score >= 70 => 'B',
            $score >= 65 => 'B-',
            $score >= 60 => 'C+',
            $score >= 55 => 'C',
            $score >= 50 => 'C-',
            default => 'D'
        };
    }

    /**
     * Calculate weekly score (0-100).
     */
    public function getWeeklyScoreAttribute(): float
    {
        $scores = [];

        // Completion rate (25%)
        $scores[] = ($this->overall_completion_rate ?? 0) * 0.25;

        // Quality score (20%)
        $scores[] = (($this->average_quality_score ?? 0) / 10 * 100) * 0.20;

        // Reading consistency (15%)
        $scores[] = ($this->reading_consistency ? 100 : 0) * 0.15;

        // Exercise consistency (15%)
        $scores[] = ($this->exercise_consistency ? 100 : 0) * 0.15;

        // Satisfaction (10%)
        $scores[] = (($this->satisfaction_avg ?? 0) / 10 * 100) * 0.10;

        // Energy maintenance (10%)
        $energyScore = ($this->average_energy_level ?? 0) >= 7 ? 100 : (($this->average_energy_level ?? 0) / 10 * 100);
        $scores[] = $energyScore * 0.10;

        // Sleep consistency (5%)
        $scores[] = ($this->sleep_consistency ? 100 : 0) * 0.05;

        return round(array_sum($scores), 1);
    }

    /**
     * Get improvement suggestions.
     */
    public function getImprovementSuggestions(): array
    {
        $suggestions = [];

        if (($this->overall_completion_rate ?? 0) < 80) {
            $suggestions[] = [
                'area' => 'Task Completion',
                'suggestion' => 'Focus on completing at least 85% of your daily tasks. Consider breaking large tasks into smaller ones.',
                'priority' => 'high'
            ];
        }

        if (($this->average_quality_score ?? 0) < 7.5) {
            $suggestions[] = [
                'area' => 'Quality Focus',
                'suggestion' => 'Improve task quality by eliminating distractions and allowing more time for important work.',
                'priority' => 'high'
            ];
        }

        if (!$this->reading_consistency) {
            $suggestions[] = [
                'area' => 'Reading Consistency',
                'suggestion' => 'Maintain your 30-minute daily reading habit. Consider reading at the same time each day.',
                'priority' => 'medium'
            ];
        }

        if (!$this->exercise_consistency) {
            $suggestions[] = [
                'area' => 'Exercise',
                'suggestion' => 'Aim for at least 5 exercise sessions per week to maintain energy and focus.',
                'priority' => 'medium'
            ];
        }

        if (($this->satisfaction_avg ?? 0) < 7) {
            $suggestions[] = [
                'area' => 'Work Satisfaction',
                'suggestion' => 'Identify what\'s causing low satisfaction and adjust your daily routine accordingly.',
                'priority' => 'medium'
            ];
        }

        if (($this->stress_level_avg ?? 0) > 6) {
            $suggestions[] = [
                'area' => 'Stress Management',
                'suggestion' => 'Implement stress reduction techniques such as meditation, breaks, or delegation.',
                'priority' => 'high'
            ];
        }

        return $suggestions;
    }

    /**
     * Get weekly highlights based on performance.
     */
    public function getWeeklyHighlights(): array
    {
        $highlights = [];

        if (($this->overall_completion_rate ?? 0) >= 90) {
            $highlights[] = "🎯 Excellent task completion rate: {$this->overall_completion_rate}%";
        }

        if (($this->average_quality_score ?? 0) >= 8.5) {
            $highlights[] = "⭐ Outstanding quality performance: {$this->average_quality_score}/10";
        }

        if ($this->reading_consistency) {
            $highlights[] = "📚 Perfect reading consistency: {$this->total_reading_minutes} minutes total";
        }

        if ($this->exercise_consistency) {
            $highlights[] = "💪 Great exercise consistency maintained";
        }

        if ($this->books_completed > 0) {
            $highlights[] = "🏆 Completed {$this->books_completed} book(s) this week";
        }

        if (($this->average_energy_level ?? 0) >= 8) {
            $highlights[] = "⚡ High energy levels maintained: {$this->average_energy_level}/10";
        }

        if ($this->total_pages_read >= 200) {
            $highlights[] = "📖 Read {$this->total_pages_read} pages this week";
        }

        return $highlights;
    }

    /**
     * Export weekly review data.
     */
    public function exportData(): array
    {
        return [
            'week_period' => $this->week_start_date->format('M j') . ' - ' . $this->week_end_date->format('M j, Y'),
            'performance_grade' => $this->performance_grade,
            'weekly_score' => $this->weekly_score,
            'metrics' => [
                'completion_rate' => $this->overall_completion_rate,
                'quality_score' => $this->average_quality_score,
                'reading_minutes' => $this->total_reading_minutes,
                'pages_read' => $this->total_pages_read,
                'books_completed' => $this->books_completed,
                'satisfaction' => $this->satisfaction_avg,
                'energy_level' => $this->average_energy_level,
            ],
            'consistency' => [
                'exercise' => $this->exercise_consistency,
                'reading' => $this->reading_consistency,
                'sleep' => $this->sleep_consistency,
            ],
            'highlights' => $this->getWeeklyHighlights(),
            'improvements' => $this->getImprovementSuggestions(),
            'category_performance' => $this->category_performance,
            'habits_analysis' => $this->habits_analysis,
        ];
    }

    /**
     * Scope to get reviews for a specific year.
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to get recent reviews.
     */
    public function scopeRecent($query, $weeks = 12)
    {
        return $query->orderBy('week_start_date', 'desc')->limit($weeks);
    }

    /**
     * Scope to get reviews ordered by week.
     */
    public function scopeOrderedByWeek($query)
    {
        return $query->orderBy('year', 'desc')->orderBy('week_number', 'desc');
    }

    /**
     * Get the previous week's review.
     */
    public function getPreviousWeek()
    {
        $prevWeek = $this->week_start_date->copy()->subWeek();
        return static::getOrCreateForWeek($prevWeek);
    }

    /**
     * Get the next week's review.
     */
    public function getNextWeek()
    {
        $nextWeek = $this->week_start_date->copy()->addWeek();
        return static::getOrCreateForWeek($nextWeek);
    }

    /**
     * Compare with previous week.
     */
    public function compareWithPreviousWeek(): array
    {
        $previousWeek = $this->getPreviousWeek();

        if (!$previousWeek->overall_completion_rate) {
            return ['message' => 'No previous week data available for comparison.'];
        }

        return [
            'completion_rate_change' => ($this->overall_completion_rate ?? 0) - ($previousWeek->overall_completion_rate ?? 0),
            'quality_score_change' => ($this->average_quality_score ?? 0) - ($previousWeek->average_quality_score ?? 0),
            'reading_time_change' => ($this->total_reading_minutes ?? 0) - ($previousWeek->total_reading_minutes ?? 0),
            'satisfaction_change' => ($this->satisfaction_avg ?? 0) - ($previousWeek->satisfaction_avg ?? 0),
            'weekly_score_change' => $this->weekly_score - $previousWeek->weekly_score,
        ];
    }

    /**
     * Auto-generate review content based on data.
     */
    public function generateReviewContent()
    {
        $highlights = $this->getWeeklyHighlights();
        $improvements = $this->getImprovementSuggestions();

        // Auto-generate week highlights
        if (!$this->week_highlights && !empty($highlights)) {
            $this->week_highlights = implode("\n• ", [''] + $highlights);
        }

        // Auto-generate improvement areas
        if (!$this->improvement_areas && !empty($improvements)) {
            $improvementTexts = array_map(fn($item) => $item['suggestion'], $improvements);
            $this->improvement_areas = implode("\n• ", [''] + $improvementTexts);
        }

        // Auto-generate celebration notes for high performance
        if (!$this->celebration_notes && $this->weekly_score >= 85) {
            $this->celebration_notes = "🎉 Excellent week with a {$this->performance_grade} grade! " .
                "Maintained high standards across multiple areas. Keep up the momentum!";
        }

        $this->save();
        return $this;
    }
}