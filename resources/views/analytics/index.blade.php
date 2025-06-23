@extends('layouts.app')

@section('title', 'Analytics - CEO Routine Tracker')

@section('content')
    <div class="analytics-page">
        <!-- Page Header -->
        <div class="page-header mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-chart-bar"></i>
                        Performance Analytics
                    </h1>
                    <p class="text-muted">Insights and trends from {{ $startDate->format('M j') }} to
                        {{ $endDate->format('M j, Y') }}</p>
                </div>

                <div class="flex gap-2">
                    <select class="form-select" onchange="changePeriod(this.value)" style="width: auto;">
                        <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 days</option>
                        <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 days</option>
                        <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 days</option>
                        <option value="365" {{ $period == 365 ? 'selected' : '' }}>Last year</option>
                    </select>
                    <button onclick="exportAnalytics()" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Overview Statistics -->
        <div class="grid grid-4 mb-6">
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['overview']['avg_completion_rate'] }}%</div>
                <div class="stat-label">Avg Completion Rate</div>
            </div>

            <div class="stat-card success">
                <div class="stat-number">{{ $analytics['overview']['avg_quality_score'] }}</div>
                <div class="stat-label">Avg Quality Score</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-number">{{ number_format($analytics['overview']['total_reading_time'] / 60, 1) }}h</div>
                <div class="stat-label">Total Reading Time</div>
            </div>

            <div class="stat-card info">
                <div class="stat-number">{{ $analytics['overview']['excellence_rate'] }}%</div>
                <div class="stat-label">Excellence Rate</div>
            </div>
        </div>

        <!-- CEO Excellence Targets -->
        <div class="card mb-6">
            <div class="card-header">
                <i class="fas fa-trophy"></i>
                CEO Excellence Targets
            </div>
            <div class="card-content">
                <div class="grid grid-3">
                    @foreach ($analytics['goal_tracking']['targets'] as $target)
                        <div class="target-item">
                            <div class="target-header">
                                <h4>{{ $target['name'] }}</h4>
                                <span class="target-badge target-{{ $target['status'] }}">
                                    {{ $target['achievement_rate'] }}%
                                </span>
                            </div>
                            <div class="target-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ $target['achievement_rate'] }}%"></div>
                                </div>
                                <div class="target-details">
                                    {{ $target['achieved_days'] }} / {{ $target['total_days'] }} days
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="overall-score mt-6 text-center">
                    <div
                        class="score-circle score-{{ $analytics['goal_tracking']['overall_score'] >= 85 ? 'excellent' : ($analytics['goal_tracking']['overall_score'] >= 70 ? 'good' : 'needs-improvement') }}">
                        <span class="score-number">{{ $analytics['goal_tracking']['overall_score'] }}%</span>
                        <span class="score-label">Overall Score</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Trends -->
        <div class="grid grid-2 mb-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i>
                    Performance Trends
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="performanceTrendsChart"></canvas>
                    </div>

                    @if (isset($analytics['performance_trends']['trend_analysis']))
                        <div class="trend-summary mt-4">
                            <div class="grid grid-3 text-center">
                                <div class="trend-item">
                                    <div
                                        class="trend-value {{ $analytics['performance_trends']['trend_analysis']['completion_trend'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $analytics['performance_trends']['trend_analysis']['completion_trend'] > 0 ? '+' : '' }}{{ $analytics['performance_trends']['trend_analysis']['completion_trend'] }}%
                                    </div>
                                    <div class="trend-label">Completion</div>
                                </div>
                                <div class="trend-item">
                                    <div
                                        class="trend-value {{ $analytics['performance_trends']['trend_analysis']['quality_trend'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $analytics['performance_trends']['trend_analysis']['quality_trend'] > 0 ? '+' : '' }}{{ $analytics['performance_trends']['trend_analysis']['quality_trend'] }}
                                    </div>
                                    <div class="trend-label">Quality</div>
                                </div>
                                <div class="trend-item">
                                    <div
                                        class="trend-value {{ $analytics['performance_trends']['trend_analysis']['satisfaction_trend'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $analytics['performance_trends']['trend_analysis']['satisfaction_trend'] > 0 ? '+' : '' }}{{ $analytics['performance_trends']['trend_analysis']['satisfaction_trend'] }}
                                    </div>
                                    <div class="trend-label">Satisfaction</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i>
                    Category Performance
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="categoryPerformanceChart"></canvas>
                    </div>

                    <div class="category-details mt-4">
                        @foreach ($analytics['category_analysis'] as $category)
                            <div class="category-item">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="category-color" style="background-color: {{ $category['color'] }};">
                                        </div>
                                        <span class="font-semibold">{{ $category['name'] }}</span>
                                    </div>
                                    <span class="text-sm font-bold">{{ $category['completion_rate'] }}%</span>
                                </div>
                                <div class="progress-bar mb-1">
                                    <div class="progress-fill"
                                        style="width: {{ $category['completion_rate'] }}%; background-color: {{ $category['color'] }}">
                                    </div>
                                </div>
                                <div class="text-sm text-muted">
                                    Quality: {{ $category['quality_score'] }}/10 •
                                    {{ $category['completed_tasks'] }}/{{ $category['total_tasks'] }} tasks
                                </div>
                            </div>
                            @if (!$loop->last)
                                <hr class="my-3">
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Patterns -->
        <div class="card mb-6">
            <div class="card-header">
                <i class="fas fa-calendar-week"></i>
                Weekly Patterns
            </div>
            <div class="card-content">
                <div class="chart-container">
                    <canvas id="weeklyPatternsChart"></canvas>
                </div>

                <div class="weekly-insights mt-4">
                    <div class="grid grid-2">
                        <div class="insight-item">
                            <h4 class="text-success">
                                <i class="fas fa-star"></i>
                                Best Day: {{ $analytics['weekly_patterns']['best_day']['day'] }}
                            </h4>
                            <p class="text-muted">{{ $analytics['weekly_patterns']['best_day']['avg_completion'] }}%
                                average completion</p>
                        </div>
                        <div class="insight-item">
                            <h4 class="text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Most Challenging: {{ $analytics['weekly_patterns']['most_challenging_day']['day'] }}
                            </h4>
                            <p class="text-muted">
                                {{ $analytics['weekly_patterns']['most_challenging_day']['avg_completion'] }}% average
                                completion</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reading Analytics -->
        <div class="grid grid-2 mb-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-book-open"></i>
                    Reading Analytics
                </div>
                <div class="card-content">
                    <div class="reading-stats">
                        <div class="grid grid-2 text-center mb-4">
                            <div>
                                <div class="stat-number-sm">{{ $analytics['reading_analytics']['total_sessions'] }}</div>
                                <div class="stat-label-sm">Sessions</div>
                            </div>
                            <div>
                                <div class="stat-number-sm">{{ $analytics['reading_analytics']['avg_session_duration'] }}m
                                </div>
                                <div class="stat-label-sm">Avg Duration</div>
                            </div>
                            <div>
                                <div class="stat-number-sm">{{ $analytics['reading_analytics']['total_pages'] }}</div>
                                <div class="stat-label-sm">Pages Read</div>
                            </div>
                            <div>
                                <div class="stat-number-sm">{{ $analytics['reading_analytics']['consistency'] }}%</div>
                                <div class="stat-label-sm">Consistency</div>
                            </div>
                        </div>

                        <div class="reading-progress">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-semibold">Reading Consistency</span>
                                <span
                                    class="text-primary font-bold">{{ $analytics['reading_analytics']['consistency'] }}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill"
                                    style="width: {{ $analytics['reading_analytics']['consistency'] }}%"></div>
                            </div>
                            <div class="text-sm text-muted mt-1">
                                {{ $analytics['reading_analytics']['reading_days'] }} reading days in period
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-battery-three-quarters"></i>
                    Energy Patterns
                </div>
                <div class="card-content">
                    <div class="energy-overview mb-4">
                        <div class="grid grid-2 text-center">
                            <div>
                                <div class="stat-number-sm">{{ $analytics['energy_patterns']['avg_morning_energy'] }}
                                </div>
                                <div class="stat-label-sm">Morning Energy</div>
                            </div>
                            <div>
                                <div class="stat-number-sm">{{ $analytics['energy_patterns']['avg_evening_energy'] }}
                                </div>
                                <div class="stat-label-sm">Evening Energy</div>
                            </div>
                        </div>
                    </div>

                    <div class="energy-change">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold">Daily Energy Change</span>
                            <span
                                class="font-bold {{ $analytics['energy_patterns']['avg_energy_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $analytics['energy_patterns']['avg_energy_change'] > 0 ? '+' : '' }}{{ $analytics['energy_patterns']['avg_energy_change'] }}
                            </span>
                        </div>
                        <div class="energy-bar">
                            <div class="energy-fill {{ $analytics['energy_patterns']['avg_energy_change'] >= 0 ? 'positive' : 'negative' }}"
                                style="width: {{ abs($analytics['energy_patterns']['avg_energy_change']) * 10 }}%"></div>
                        </div>
                    </div>

                    <div class="chart-container mt-4" style="height: 200px;">
                        <canvas id="energyPatternsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Insights -->
        <div class="card mb-6">
            <div class="card-header">
                <i class="fas fa-lightbulb"></i>
                Task Insights
            </div>
            <div class="card-content">
                <div class="grid grid-2">
                    <!-- Top Performers -->
                    <div>
                        <h4 class="font-semibold mb-3 text-success">
                            <i class="fas fa-trophy"></i>
                            Top Performing Tasks
                        </h4>
                        @if ($analytics['task_insights']['top_performers']->isNotEmpty())
                            @foreach ($analytics['task_insights']['top_performers'] as $task)
                                <div class="task-insight-item">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold">{{ $task['task'] }}</div>
                                            <div class="text-sm text-muted">{{ $task['category'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-success font-bold">{{ $task['avg_quality'] }}/10</div>
                                            <div class="text-xs text-muted">{{ $task['completion_rate'] }}% completed
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">No task data available for this period.</p>
                        @endif
                    </div>

                    <!-- Needs Improvement -->
                    <div>
                        <h4 class="font-semibold mb-3 text-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Needs Improvement
                        </h4>
                        @if ($analytics['task_insights']['needs_improvement']->isNotEmpty())
                            @foreach ($analytics['task_insights']['needs_improvement'] as $task)
                                <div class="task-insight-item">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold">{{ $task['task'] }}</div>
                                            <div class="text-sm text-muted">{{ $task['category'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-warning font-bold">{{ $task['completion_rate'] }}%</div>
                                            <div class="text-xs text-muted">{{ $task['priority'] }} priority</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-success">All tasks performing well!</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Productivity Insights -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-area"></i>
                Productivity Insights
            </div>
            <div class="card-content">
                <div class="grid grid-2">
                    <div>
                        <h4 class="font-semibold mb-3">Peak Productivity Hours</h4>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="productivityHoursChart"></canvas>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold mb-3">Most Productive Days</h4>
                        @if (isset($analytics['productivity_insights']['most_productive_days']))
                            @foreach ($analytics['productivity_insights']['most_productive_days'] as $day)
                                <div class="productive-day-item">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-semibold">
                                                {{ \Carbon\Carbon::parse($day['date'])->format('M j, Y') }}</div>
                                            <div class="text-sm text-muted">{{ $day['tasks_completed'] }} tasks completed
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-success font-bold">{{ $day['avg_quality'] }}/10</div>
                                            <div class="text-xs text-muted">
                                                {{ CEOTracker . formatDuration($day['total_time']) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .target-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background-color: #f8fafc;
        }

        .target-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .target-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .target-excellent {
            background-color: var(--success-color);
            color: white;
        }

        .target-good {
            background-color: var(--warning-color);
            color: white;
        }

        .target-needs_improvement {
            background-color: var(--danger-color);
            color: white;
        }

        .target-details {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .score-circle {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            position: relative;
        }

        .score-circle::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            padding: 4px;
            background: conic-gradient(from 0deg, var(--primary-color), var(--success-color));
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: subtract;
            -webkit-mask-composite: subtract;
        }

        .score-excellent {
            background-color: #dcfce7;
            color: #166534;
        }

        .score-good {
            background-color: #fef3c7;
            color: #92400e;
        }

        .score-needs-improvement {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .score-number {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .score-label {
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .trend-item {
            padding: 0.5rem;
        }

        .trend-value {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .trend-label {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .category-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .category-item {
            margin-bottom: 1rem;
        }

        .stat-number-sm {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label-sm {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .energy-bar {
            height: 8px;
            background-color: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }

        .energy-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .energy-fill.positive {
            background-color: var(--success-color);
        }

        .energy-fill.negative {
            background-color: var(--danger-color);
        }

        .task-insight-item {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            background-color: #f8fafc;
        }

        .productive-day-item {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            background-color: #f8fafc;
        }

        .insight-item h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // Initialize all charts
        document.addEventListener('DOMContentLoaded', function() {
            initPerformanceTrendsChart();
            initCategoryPerformanceChart();
            initWeeklyPatternsChart();
            initEnergyPatternsChart();
            initProductivityHoursChart();
        });

        // Performance trends chart
        function initPerformanceTrendsChart() {
            const ctx = document.getElementById('performanceTrendsChart').getContext('2d');
            const data = @json($analytics['performance_trends']['daily_data']);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date_formatted),
                    datasets: [{
                        label: 'Completion Rate (%)',
                        data: data.map(d => d.completion_rate),
                        borderColor: 'rgb(37, 99, 235)',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.3,
                        yAxisID: 'y'
                    }, {
                        label: 'Quality Score',
                        data: data.map(d => d.quality_score),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3,
                        yAxisID: 'y1'
                    }, {
                        label: 'Satisfaction',
                        data: data.map(d => d.satisfaction),
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.3,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Completion Rate (%)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            max: 10,
                            title: {
                                display: true,
                                text: 'Score (1-10)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        // Category performance chart
        function initCategoryPerformanceChart() {
            const ctx = document.getElementById('categoryPerformanceChart').getContext('2d');
            const data = @json($analytics['category_analysis']);

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.name),
                    datasets: [{
                        data: data.map(d => d.completion_rate),
                        backgroundColor: data.map(d => d.color),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Weekly patterns chart
        function initWeeklyPatternsChart() {
            const ctx = document.getElementById('weeklyPatternsChart').getContext('2d');
            const data = @json($analytics['weekly_patterns']['weekly_patterns']);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.values(data).map(d => d.day),
                    datasets: [{
                        label: 'Completion Rate (%)',
                        data: Object.values(data).map(d => d.avg_completion),
                        backgroundColor: 'rgba(37, 99, 235, 0.7)',
                        borderColor: 'rgb(37, 99, 235)',
                        borderWidth: 1
                    }, {
                        label: 'Quality Score',
                        data: Object.values(data).map(d => d.avg_quality *
                            10), // Scale to match completion rate
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Energy patterns chart
        function initEnergyPatternsChart() {
            const ctx = document.getElementById('energyPatternsChart').getContext('2d');
            const data = @json($analytics['energy_patterns']['energy_by_day'] ?? []);

            if (Object.keys(data).length === 0) return;

            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dayNames,
                    datasets: [{
                        label: 'Morning Energy',
                        data: dayNames.map((_, index) => data[index]?.morning || 0),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3
                    }, {
                        label: 'Evening Energy',
                        data: dayNames.map((_, index) => data[index]?.evening || 0),
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10
                        }
                    }
                }
            });
        }

        // Productivity hours chart
        function initProductivityHoursChart() {
            const ctx = document.getElementById('productivityHoursChart').getContext('2d');
            const data = @json($analytics['productivity_insights']['peak_hours'] ?? []);

            if (Object.keys(data).length === 0) return;

            const hours = Object.keys(data).map(h => h + ':00');
            const counts = Object.values(data).map(d => d.count);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'Tasks Completed',
                        data: counts,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Change period
        function changePeriod(period) {
            window.location.href = `/analytics?period=${period}`;
        }

        // Export analytics
        function exportAnalytics() {
            window.open(`/analytics/export?period={{ $period }}`, '_blank');
        }
    </script>
@endpush
